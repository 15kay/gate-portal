#!/bin/bash
# Add faculty column to alumni_profiles table on production server

mysql -h gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com -u admin -p'Gate123-portal' gate_portal <<'EOF'
ALTER TABLE alumni_profiles ADD COLUMN faculty VARCHAR(255) DEFAULT NULL AFTER degree;
EOF

echo "Faculty column added successfully"
