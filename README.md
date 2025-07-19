![System Logo](https://github.com/CACabusas/MVFM/blob/main/logo%20simple.png?raw=true)
A web-based fleet management system prototype made for National Power Corporation - Mindanao Generation

# Notes:
- The system runs via **XAMPP** (together with Apache and MySQL), so make sure your system has the program installed.
- A third-party library is used for generating QR codes (PHPQRCode), and the library is located in the `/lib` folder. The library is compressed, thus it is not functional. Please do make sure to extract the ZIP file on your PC in the same folder to make the library functional.
- The video for the login page is downscaled from 1080p to 480p to mitigate the issue of GitHub not being able to handle files larger than 25MB. (File name: `video-cropped-480p.mp4`)

# Walkthrough:
## 1. Login
![Login Page](https://github.com/CACabusas/MVFM/blob/main/walkthrough/01_login_1.png?raw=true)
1. User type selection ('Officer' or 'Admin')
2. Password input box
3. Log in button
4. Forms
5. Policies
6. Send Feedback
7. Contact
8. About

![Login Page](https://github.com/CACabusas/MVFM/blob/main/walkthrough/02_login_2.png?raw=true)
9. Forms modal (choose a form to print)<br/>
10. Policies modal (choose a policy to view)<br/>
11. Feedback modal (view the QR code to proceed to the feedback form)

## 2. Contact
![Contact Page](https://github.com/CACabusas/MVFM/blob/main/walkthrough/03_contact.png?raw=true)
1. System Logo (clicking will redirect the user back to the login page)
2. Back button (clicking will redirect the user back to the login page)
3. Log In (clicking will redirect the user back to the login page)
4. Search box
5. Contacts list filter
6. Contacts list grid

## 3. About
![About Page](https://github.com/CACabusas/MVFM/blob/main/walkthrough/04_about.png?raw=true)
The text for the page is editable in the **_Miscellaneous_** page (`misc.php`) or directly in `about_content.txt`.

## 4. Dashboard
![Dashboard](https://github.com/CACabusas/MVFM/blob/main/walkthrough/05_dashboard_1.png?raw=true)
1. Vehicles (vehicle list)
2. Maintenance Reports
3. Fuel & Mileage Reports
4. Vehicle History Reports
5. Misc (miscellaneous page)
6. Logout button
7. Vehicle statistics at a glance
8. Driver Availability Calendar
9. Schedule Drive modal (for adding/editing/deleting a schedule)

![Dashboard](https://github.com/CACabusas/MVFM/blob/main/walkthrough/06_dashboard_2.png?raw=true)
10. Vehicle Assessment selection grid

## 5. Vehicle Assessment
## 6. Vehicles
## 7. Maintenance Reports
## 8. Fuel & Mileage Reports
## 9. Vehicle History Reports
## 10. Miscellaneous
