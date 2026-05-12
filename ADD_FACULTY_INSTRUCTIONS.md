# Add Faculty Column to Database

## Step 1: SSH into the server
```bash
ssh ubuntu@13.60.96.145
```

## Step 2: Navigate to the project directory
```bash
cd /var/www/html/gate-portal
```

## Step 3: Pull the latest changes
```bash
sudo git pull origin main
```

## Step 4: Run the SQL command to add faculty column
```bash
mysql -h gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com -u admin -p'Gate123-portal' gate_portal -e "ALTER TABLE alumni_profiles ADD COLUMN faculty VARCHAR(255) DEFAULT NULL AFTER degree;"
```

## Step 5: Verify the column was added
```bash
mysql -h gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com -u admin -p'Gate123-portal' gate_portal -e "DESCRIBE alumni_profiles;"
```

You should see the `faculty` column listed after the `degree` column.

## Step 6: Test the application
Visit http://13.60.96.145/admin/view_alumni.php?id=4 to verify the page loads without errors.

---

## What was changed:

1. **Database**: Added `faculty` column to `alumni_profiles` table
2. **admin/view_alumni.php**: Updated to display faculty in the Academic Information section
3. **Faculty values**: The system uses these 7 WSU faculties:
   - Faculty of Engineering, Built Environment and Information Technology
   - Faculty of Law, Humanities and Social Sciences
   - Faculty of Management and Public Administration Sciences
   - Faculty of Economics and Financial Sciences
   - Faculty of Medicine and Health Sciences
   - Faculty of Natural Sciences
   - Faculty of Education

Alumni can select their faculty when editing their profile at /alumni/profile.php
