# 🎓 Smart Student Record & Academic Analytics System

![PHP](https://img.shields.io/badge/PHP-8.0-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-XAMPP-CA2136?style=for-the-badge&logo=apache&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

A full-stack web application for managing student academic records, faculty, attendance, marks, GPA/CGPA, and generating analytical reports — built with PHP, MySQL, and Bootstrap.

## 📸 Screenshots



*(Add screenshots of your project here by dragging images into this section on GitHub)*

---

## 🚀 Features

### 👨‍💼 Admin
- Manage Students, Faculty, Courses, and Departments (full CRUD)
- Assign faculty to courses and enroll students
- View complete student academic records
- Download PDF reports — Report Cards, Attendance Reports, Department Reports
- At-a-glance dashboard with live counts

### 👨‍🏫 Faculty
- Mark daily attendance (Present / Absent) with bulk mark-all option
- Upload marks — CAT 1, CAT 2, Assignment, Final Exam
- View per-student attendance summary with low attendance alerts (< 75%)

### 🎓 Student
- Personal dashboard with CGPA, attendance %, marks, and grades
- Semester-wise SGPA / CGPA history
- Performance prediction — Excellent / Good / Average / Needs Improvement / At Risk
- Placement eligibility check (CGPA ≥ 7, no backlogs)
- Scholarship eligibility check (CGPA ≥ 8, attendance ≥ 80%)
- Download personal PDF report card

### 📊 Analytics & Intelligence
- Automatic GPA and CGPA calculation using credit-weighted formula
- Grade computation (O / A+ / A / B+ / B / C / F) from total marks
- Performance prediction algorithm based on marks and attendance
- At-risk student detection (low CGPA + low attendance)

### 📄 PDF Generation
- Student Report Cards (marks, grades, GPA history, eligibility)
- Course-wise Attendance Reports with low attendance highlights
- Department Academic Reports (all students, CGPA, placement/scholarship eligibility)


## 🛠️ Tech Stack
| Layer | Technology |
| Frontend | HTML5, CSS3, Bootstrap 5.3, JavaScript |
| Backend | PHP 8.0 (PDO, prepared statements) |
| Database | MySQL / MariaDB |
| Server | Apache (via XAMPP) |
| PDF Generation | FPDF (PHP library) |
| Fonts & Icons | Google Fonts (Inter), Bootstrap Icons |


## 🧠 What I Learned

- Designing a normalized relational database schema for a real-world use case
- Implementing secure PHP sessions and role-based routing from scratch
- Writing complex SQL queries involving multiple JOINs, GROUP BY, and subqueries
- Building reusable PHP includes for shared layout and logic
- Generating formatted PDF documents server-side using FPDF
- Handling database transactions to ensure data consistency
- Building a complete CRUD system across multiple entities with proper validation


---

> Built as part of an internship project to demonstrate full-stack web development skills using PHP, MySQL, and Bootstrap.
