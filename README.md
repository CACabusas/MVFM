![System Logo](https://github.com/CACabusas/MVFM/blob/main/logo%20simple.png?raw=true)
_Mindanao Generation Vehicle Fleet Management_ (**MVFM**) is a web-based fleet management system **prototype** made for **National Power Corporation - Mindanao Generation**

# Prerequisites:
To run the system, the following **_must_** be installed in your PC:
1. [**XAMPP**](https://www.apachefriends.org/download.html)
   - Together with **Apache** and **MySQL**
2. [phpqrcode](https://github.com/lasalesi/phpqrcode)
   - A ZIP folder is already included in the `/lib` folder of the repository, so just extract it in the same folder to install it.

> [!IMPORTANT]
> - The libraries for generating the graphs are just fetched online. So if your system is not connected to the internet, the graph may not show up. To mitigate the issue, you can download the libraries, add it in the `/lib` folder, then import it especially in `dashboard.php` and `assessment.php`.

> [!NOTE]
> - The video for the login page is downscaled from 1080p to 480p to mitigate the issue of GitHub not being able to handle files larger than 25MB. (File name: `video-cropped-480p.mp4`)

# Walkthrough:
This section will briefly showcase the list of features/functionalities each page has.

## 1. Login
![Login](https://github.com/CACabusas/MVFM/blob/main/walkthrough/01_login_1.png?raw=true)
1. User type selection (`Transportation Officer` or `System Admin`)<br/>
2. Password input box<br/>
3. Log in button<br/>
4. Forms<br/>
5. Policies<br/>
6. Send Feedback<br/>
7. Contact<br/>
8. About

![Login](https://github.com/CACabusas/MVFM/blob/main/walkthrough/02_login_2.png?raw=true)
9. Forms modal (choose a form to print)<br/>
10. Policies modal (choose a policy to view)<br/>
11. Feedback modal (view the QR code to proceed to the feedback form)

## 2. Contact
![Contact](https://github.com/CACabusas/MVFM/blob/main/walkthrough/03_contact.png?raw=true)
1. System Logo (clicking will redirect the user back to the login page)<br/>
2. Back button (clicking will redirect the user back to the login page)<br/>
3. Log In (clicking will redirect the user back to the login page)<br/>
4. Search box<br/>
5. Contacts list filter<br/>
6. Contacts list grid

## 3. About
![About](https://github.com/CACabusas/MVFM/blob/main/walkthrough/04_about.png?raw=true)
The text for the page is editable in the **_Miscellaneous_** page (`misc.php`) or directly in `about_content.txt`.

## 4. Dashboard
![Dashboard](https://github.com/CACabusas/MVFM/blob/main/walkthrough/05_dashboard_1.png?raw=true)
1. Vehicles (vehicle list)<br/>
2. Maintenance Reports<br/>
3. Fuel & Mileage Reports<br/>
4. Vehicle History Reports<br/>
5. Misc (miscellaneous page)<br/>
6. Logout button<br/>
7. Vehicle statistics at a glance<br/>
8. Driver Availability Calendar<br/>
9. Schedule Drive modal (for adding/editing/deleting a schedule)

![Dashboard](https://github.com/CACabusas/MVFM/blob/main/walkthrough/06_dashboard_2.png?raw=true)
10. Vehicle Assessment selection grid

## 5. Vehicle Assessment
![Vehicle Assessment](https://github.com/CACabusas/MVFM/blob/main/walkthrough/07_assessment_1.png?raw=true)
1. System Logo (clicking will redirect the user back to the dashboard)<br/>
2. Back button<br/>
3. Assessment Year (user can change the year as long as the said year has data)<br/>
4. Vehicle maintenance statistics

![Vehicle Assessment](https://github.com/CACabusas/MVFM/blob/main/walkthrough/08_assessment_2.png?raw=true)
5. Vehicle fuel & mileage statistics

## 6. Vehicles
![Vehicle List](https://github.com/CACabusas/MVFM/blob/main/walkthrough/09_vehicle_1.png?raw=true)
1. Add Vehicle<br/>
2. Print All Vehicles (will prepare a document that is ready to be printed)<br/>
3. Vehicle selection grid<br/>
4. Vehicle Details modal (for adding/editing vehicles)

![Vehicle List](https://github.com/CACabusas/MVFM/blob/main/walkthrough/10_vehicle_2.png?raw=true)
5. Edit Details<br/>
6. Delete Vehicle from list (will delete all the data linked to it)<br/>
7. Print (will prepare a document that is ready to be printed)

## 7. Maintenance Reports
![Maintenance Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/11_maintenance_1.png?raw=true)
1. Vehicle selection grid

### 7.1 Maintenance Report Selection
![Maintenance Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/12_maintenance_2.png?raw=true)
1. Year selection for Annual Report<br/>
2. Year selection for Quarterly Report<br/>
3. Quarter selection

### 7.2 Annual Maintenance Report
![Maintenance Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/13_maintenance_3.png?raw=true)
1. Generate Report button (will prepare a document that is ready to be printed)

### 7.3 Quarterly Maintenance Report
![Maintenance Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/14_maintenance_4.png?raw=true)
1. Add new data<br/>
2. Edit data<br/>
3. Delete data<br/>
4. Generate Report button (will prepare a document that is ready to be printed)

**Data Modal**
![Maintenance Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/15_maintenance_5.png?raw=true)
5. Date selection<br/>
6. Repair Shop<br/>
7. Reference Type (`Purchase Order Number`, `Contract Number`, or `Admin`)<br/>
8. Description<br/>
9. Maintenance Type (`Preventive` or `Corrective`)<br/>
10. Maintenance Cost<br/>
11. Save button<br/>
12. Cancel button

## 8. Fuel & Mileage Reports
![Fuel & Mileage Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/16_mileage_1.png?raw=true)
1. Vehicle selection grid

### 8.1 Fuel & Mileage Report Selection
![Fuel & Mileage Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/17_mileage_2.png?raw=true)
1. Year selection for Annual/Quarterly Report<br/>
2. Year selection for Monthly Report<br/>
3. Month selection

### 8.2 Annual/Quarterly Fuel & Mileage Report
![Fuel & Mileage Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/18_mileage_3.png?raw=true)
1. Report type selection (`Annual Report` or one of the four quarters)<br/>
- The Generate Report button is beside it (will prepare a document that is ready to be printed)<br/>
2. Quarterly Breakdown table

### 8.3 Monthly Fuel & Mileage Report
![Fuel & Mileage Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/19_mileage_4.png?raw=true)
1. Add new data<br/>
2. Show Summary button<br/>
3. Edit data<br/>
4. Delete data<br/>
5. Generate Report button (will prepare a document that is ready to be printed)<br/>
6. Show Summary modal

**Data Modal**
![Fuel & Mileage Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/20_mileage_5.png?raw=true)
7. Date selection<br/>
8. Ododmeter Beginning (data will be fetched from the **Ododmeter Ending** from the previous data)<br/>
9. Odometer Ending<br/>
10. Distance travelled<br/>
11. Liters purchased<br/>
12. Cost of the fuel purchased<br/>
13. Invoice number<br/>
14. Gas Station<br/>
15. Driver name<br/>
16. Save button<br/>
17. Cancel button

## 9. Vehicle History Reports
![Vehicle History Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/21_history_1.png?raw=true)
1. Vehicle selection grid

### 9.1 Complete Vehicle History
![Vehicle History Reports](https://github.com/CACabusas/MVFM/blob/main/walkthrough/22_history_2.png?raw=true)
1. Generate Report button (will prepare a document that is ready to be printed)

## 10. Miscellaneous
> [!IMPORTANT]
> When logging in as `System Admin`, the user will be directed to this page instead of the Dashboard (`dashboard.php`)

![Miscellaneous](https://github.com/CACabusas/MVFM/blob/main/walkthrough/23_misc_1.png?raw=true)
1. Add Form/Policy<br/>

**Add/Edit Form/Policy Modal**<br/>
2. Form/Policy Name<br/>
3. Type (`Form` or `Policy`)<br/>
4. Source (from local system via `Upload File` or from the internet via `External URL`)<br/>
5. Save button<br/><br/>
6. Manage Forms section (to edit/delete existing forms)<br/>
7. Manage Policies section (to edit/delete existing policies)<br/>
8. URL form to generate QR code for Feedback in the **_Login_** page (`login.php`)<br/>
9. [_System Admin Only_] Account Type selection (`Transportation Officer Account` or `System Admin Account`)<br/>
10. [_System Admin Only_] Change Password button

![Miscellaneous](https://github.com/CACabusas/MVFM/blob/main/walkthrough/24_misc_2.png?raw=true)
11. [_System Admin Only_] Edit About<br/>
12. Add Contact button<br/>
13. Search bar for searching contacts<br/>
14. Contacts filter button<br/>
15. Contact card<br/>
16. Edit contact button<br/>
17. Delete contact button

**Add/Edit Contact Modal**<br/>
18. Contact name<br/>
19. Contact type (`Driver`, `Supplier`, or `End-User`)<br/>
20. Contact Number<br/>
21. Email Address<br/>
22. Upload photo for the Contact (optional)<br/>
23. Save contact<br/>
24. Cancel button
