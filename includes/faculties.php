<?php
const WSU_FACULTIES = [
    'Faculty of Engineering, Built Environment and Information Technology' => [
        'Department of Civil Engineering',
        'Department of Built Environment',
        'Department of Network & Information Technology',
        'Department of Electrical Engineering',
        'Department of Mechanical Engineering',
        'Department of Business & Application Development',
        'Department of Applied Informatics and Mathematical Sciences',
    ],
    'Faculty of Law, Humanities and Social Sciences' => [
        'Arts',
        'School of Law',
        'Social Sciences',
    ],
    'Faculty of Management and Public Administration Sciences' => [
        'Department of Management',
        'Department of Human Resources Management',
        'Department of Marketing, Public Relations & Communication',
        'Department of Tourism & Hospitality',
        'Department of Public Management',
        'Department of Governance & Administrative Management',
    ],
    'Faculty of Economics and Financial Sciences' => [
        'Department of Business Management and Economics',
        'Department of Accounting Sciences',
        'Department of Management Accounting and Finance',
        'Department of Auditing',
    ],
    'Faculty of Medicine and Health Sciences' => [
        'Department of Health Professions Education',
        'Department of Family Medicine and Rural Health',
        'Department of Human Biology',
        'School of Pharmacology',
        'Department of Laboratory Medicine and Pathology',
        'Department of Nursing Sciences',
        'Department of Obstetrics and Gynaecology',
        'Department of Paediatrics and Child Health',
        'Department of Psychiatry',
        'Department of Surgery',
        'School of Public Health',
        'Department of Rehabilitation Medicine',
    ],
    'Faculty of Natural Sciences' => [
        'Department of Applied Sciences',
        'Department of Biological and Environmental Sciences',
        'Department of Chemical and Physical Sciences',
        'Department of Mathematical Sciences and Computing',
    ],
    'Faculty of Education' => [
        'Department of Business Management',
        'Continuing Professional Teacher Development',
        'Department of Humanities and Creative',
        'Department of Adult and Education Foundations',
        'Foundation Phase and Educational Foundations Education',
        'Department of Mathematics, Science and Technology',
    ],
];

/**
 * Renders a faculty + department pair of selects.
 * $sel_faculty and $sel_dept are the currently selected values.
 */
function wsu_faculty_selects(string $sel_faculty = '', string $sel_dept = ''): void {
    $faculties = WSU_FACULTIES;
    echo '<div class="form-group">';
    echo '<label>Faculty</label>';
    echo '<select name="faculty" id="wsu-faculty" onchange="updateDepts()" required>';
    echo '<option value="">— Select Faculty —</option>';
    foreach ($faculties as $fac => $depts) {
        $sel = ($sel_faculty === $fac) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($fac) . '"' . $sel . '>' . htmlspecialchars($fac) . '</option>';
    }
    echo '</select></div>';

    echo '<div class="form-group">';
    echo '<label>Department</label>';
    echo '<select name="department" id="wsu-dept" required>';
    echo '<option value="">— Select Department —</option>';
    // Pre-populate if faculty already selected
    if ($sel_faculty && isset($faculties[$sel_faculty])) {
        foreach ($faculties[$sel_faculty] as $dept) {
            $sel = ($sel_dept === $dept) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($dept) . '"' . $sel . '>' . htmlspecialchars($dept) . '</option>';
        }
    }
    echo '</select></div>';

    // JS data + updater
    $json = json_encode($faculties, JSON_HEX_APOS | JSON_HEX_QUOT);
    echo <<<JS
    <script>
    var WSU_FACULTIES = {$json};
    function updateDepts() {
        var fac   = document.getElementById('wsu-faculty').value;
        var sel   = document.getElementById('wsu-dept');
        var depts = WSU_FACULTIES[fac] || [];
        sel.innerHTML = '<option value="">— Select Department —</option>';
        depts.forEach(function(d) {
            var o = document.createElement('option');
            o.value = d; o.textContent = d;
            sel.appendChild(o);
        });
    }
    </script>
    JS;
}
