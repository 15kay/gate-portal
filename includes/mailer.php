<?php
function send_mail(string $to, string $subject, string $html, array $attachments = []): bool {
    $host     = setting('smtp_host',      '');
    $port     = (int)setting('smtp_port', '587');
    $user     = setting('smtp_user',      '');
    $pass     = setting('smtp_pass',      '');
    $fromName = setting('smtp_from_name', 'GATE Portal');

    if (!$host || !$user || !$pass) {
        $h  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $h .= "From: {$fromName} <noreply@gateportal.ac>\r\n";
        $ok = @mail($to, $subject, $html, $h);
        if (!$ok && function_exists('log_app_error')) {
            log_app_error('network', 'php mail() failed (no SMTP config)', [
                'to'      => $to,
                'subject' => $subject,
            ]);
        }
        return $ok;
    }

    try {
        $boundary = 'GATE_' . md5(uniqid());
        $hasAttach = !empty($attachments);

        // Build MIME body
        if ($hasAttach) {
            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($html)) . "\r\n";
            foreach ($attachments as $a) {
                if (!file_exists($a['path'])) {
                    if (function_exists('log_app_error')) {
                        log_app_error('filesystem', 'Mail attachment not found', ['path' => $a['path']]);
                    }
                    continue;
                }
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Type: {$a['mime']}; name=\"{$a['name']}\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"{$a['name']}\"\r\n\r\n";
                $body .= chunk_split(base64_encode(file_get_contents($a['path']))) . "\r\n";
            }
            $body .= "--{$boundary}--\r\n";
            $contentType = "multipart/mixed; boundary=\"{$boundary}\"";
        } else {
            $body        = chunk_split(base64_encode($html));
            $contentType = "text/html; charset=UTF-8";
        }

        // Open socket
        $sock = @fsockopen("tcp://{$host}", $port, $errno, $errstr, 15);
        if (!$sock) {
            if (function_exists('log_app_error')) {
                log_app_error('network', "SMTP socket connection failed: {$errstr}", [
                    'host'  => $host,
                    'port'  => (string)$port,
                    'errno' => (string)$errno,
                ]);
            }
            return false;
        }
        stream_set_timeout($sock, 15);

        $read = function() use ($sock) {
            $resp = '';
            while ($line = fgets($sock, 512)) {
                $resp .= $line;
                if ($line[3] === ' ') break; // last line of response
            }
            return $resp;
        };
        $send = fn($cmd) => fputs($sock, $cmd . "\r\n");

        $read(); // greeting
        $send("EHLO gateportal.local");
        $ehlo = '';
        while ($line = fgets($sock, 512)) {
            $ehlo .= $line;
            if ($line[3] === ' ') break;
        }

        // STARTTLS
        $send("STARTTLS"); $read();
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            if (function_exists('log_app_error')) {
                log_app_error('network', 'SMTP STARTTLS negotiation failed', [
                    'host' => $host,
                    'port' => (string)$port,
                ]);
            }
            fclose($sock);
            return false;
        }

        // Re-EHLO after TLS
        $send("EHLO gateportal.local");
        while ($line = fgets($sock, 512)) { if ($line[3] === ' ') break; }

        // AUTH LOGIN
        $send("AUTH LOGIN"); $read();
        $send(base64_encode($user)); $read();
        $send(base64_encode($pass));
        $authResp = $read();
        if (substr(trim($authResp), 0, 3) !== '235') {
            if (function_exists('log_app_error')) {
                log_app_error('network', 'SMTP authentication failed', [
                    'host'   => $host,
                    'user'   => $user,
                    'server' => trim($authResp),
                ]);
            }
            fclose($sock);
            return false;
        }

        $send("MAIL FROM:<{$user}>"); $read();
        $send("RCPT TO:<{$to}>"); $read();
        $send("DATA"); $read();

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$user}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        if ($hasAttach) {
            $headers .= "Content-Type: {$contentType}\r\n";
            $send($headers . "\r\n" . $body . "\r\n.");
        } else {
            $headers .= "Content-Type: {$contentType}\r\nContent-Transfer-Encoding: base64\r\n";
            $send($headers . "\r\n" . $body . "\r\n.");
        }

        $sendResp = $read();
        $send("QUIT"); fclose($sock);

        $ok = substr(trim($sendResp), 0, 3) === '250';
        if (!$ok && function_exists('log_app_error')) {
            log_app_error('network', 'SMTP DATA rejected by server', [
                'to'       => $to,
                'host'     => $host,
                'response' => trim($sendResp),
            ]);
        }
        return $ok;

    } catch (Throwable $e) {
        if (function_exists('log_app_error')) {
            log_app_error('network', 'send_mail exception: ' . $e->getMessage(), [
                'to'    => $to,
                'class' => get_class($e),
                'file'  => $e->getFile(),
                'line'  => (string)$e->getLine(),
            ]);
        }
        return false;
    }
}
