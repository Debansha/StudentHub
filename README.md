# 🎓 Smart Student Record & Academic Analytics System

![PHP](https://img.shields.io/badge/PHP-8.0-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-XAMPP-CA2136?style=for-the-badge&logo=apache&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

A full-stack web application for managing student academic records, faculty, attendance, marks, GPA/CGPA, and generating analytical reports — built with PHP, MySQL, and Bootstrap.

---

## 📸 Screenshots

> Login Page · Admin Dashboard · Student Dashboard · PDF Report Card

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

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Bootstrap 5.3, JavaScript |
| Backend | PHP 8.0 (PDO, prepared statements) |
| Database | MySQL / MariaDB |
| Server | Apache (via XAMPP) |
| PDF Generation | FPDF (PHP library) |
| Fonts & Icons | Google Fonts (Inter), Bootstrap Icons |

---

## 🗄️ Database Schema

11 relational tables with proper foreign keys and cascading deletes:

```
users → students / faculty
departments → students / faculty / courses
courses → course_assignments / enrollments / attendance / marks / backlogs
students → enrollments / attendance / marks / semester_gpa / backlogs
```

Key tables: `users`, `students`, `faculty`, `departments`, `courses`,
`course_assignments`, `enrollments`, `attendance`, `marks`, `semester_gpa`, `backlogs`

---

## ⚙️ Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.0+)
- A modern browser (Chrome / Firefox)

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/smart-student-system.git
```

**2. Move to XAMPP's htdocs**
```
Copy the smart_student_system folder to:
C:\xampp\htdocs\   (Windows)
/opt/lampp/htdocs/ (Linux)
```

**3. Set up the database**
- Start Apache and MySQL in XAMPP Control Panel
- Open `http://localhost/phpmyadmin`
- Click **Import** → select `smart_student_system.sql` → click **Go**
- Optionally import `seed_20_students.sql` for demo data

**4. Configure database connection**

Open `config/db.php` and verify:
```php
$host   = 'localhost';
$dbname = 'smart_student_system';
$dbuser = 'root';
$dbpass = '';  // Leave empty for default XAMPP setup
```

**5. Install FPDF (for PDF generation)**
- Download from [fpdf.org](http://www.fpdf.org)
- Place the extracted folder inside `libs/fpdf/`
- Ensure `libs/fpdf/fpdf.php` exists

**6. Run the application**
```
http://localhost/smart_student_system/
```

---

## 🔐 Default Login Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Student (Demo) | `CSE2021001` | `password123` |
| Faculty (Demo) | `FAC001` | `password123` |
| HOD | `HOD001` | `password123` |

> ⚠️ Change these credentials before deploying to any public server.

---

## 📁 Project Structure

```
smart_student_system/
├── config/
│   └── db.php                  # Database connection (PDO)
├── includes/
│   ├── auth.php                # Session + role-based access control
│   ├── gpa.php                 # GPA/CGPA calculation + grade logic
│   ├── header.php              # Shared navbar and HTML head
│   └── footer.php              # Shared footer
├── admin/
│   ├── dashboard.php
│   ├── students/               # Add / Edit / Delete / List students
│   ├── faculty/                # Add / Edit / Delete / List faculty
│   ├── courses/                # Add / Edit / Delete / List + assign faculty
│   ├── enrollments/            # Enroll students into courses
│   └── reports/                # Student view + PDF generators
├── faculty/
│   ├── dashboard.php
│   ├── attendance/             # Mark attendance + summary view
│   └── marks/                  # Enter CAT1, CAT2, Assignment, Final
├── student/
│   └── dashboard.php           # Full academic profile view
├── assets/
│   └── css/style.css           # Custom stylesheet
├── libs/
│   └── fpdf/                   # FPDF library (not included — download separately)
├── login.php
├── logout.php
├── index.php                   # Entry point — redirects by role
├── smart_student_system.sql    # Full database schema
└── seed_20_students.sql        # Demo data (20 students, faculty, marks, attendance)
```

---

## 🎯 Key Concepts Demonstrated

- **MVC-style separation** — logic in PHP, presentation in HTML/Bootstrap, data in MySQL
- **Role-based access control** — three distinct roles with session-based gating
- **PDO with prepared statements** — SQL injection prevention throughout
- **Database transactions** — atomic operations for student creation (user + profile together)
- **Cascading deletes** — referential integrity maintained via foreign keys
- **Computed columns** — `total` marks auto-calculated at DB level
- **Stored procedures** — used in seed data generation for attendance
- **PDF generation** — server-side PDF rendering with FPDF
- **GPA algorithm** — credit-weighted average across courses and semesters
- **Performance prediction** — rule-based scoring using marks + attendance data

---

## 🧠 What I Learned

- Designing a normalized relational database schema for a real-world use case
- Implementing secure PHP sessions and role-based routing from scratch
- Writing complex SQL queries involving multiple JOINs, GROUP BY, and subqueries
- Building reusable PHP includes for shared layout and logic
- Generating formatted PDF documents server-side using FPDF
- Handling database transactions to ensure data consistency
- Building a complete CRUD system across multiple entities with proper validation

---

## 🔮 Future Improvements

- [ ] Analytics dashboard with Chart.js (GPA trend, attendance trend graphs)
- [ ] Email notifications via PHPMailer for low attendance alerts
- [ ] Student photo upload
- [ ] Password change functionality for all roles
- [ ] Timetable management module
- [ ] Mobile-responsive improvements
- [ ] REST API layer for future mobile app integration

---

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [linkedin.com/in/yourprofile](https://linkedin.com/in/yourprofile)

---

## 📄 License

This project is licensed under the MIT License — feel free to use it for educational purposes.

---

> Built as part of an internship project to demonstrate full-stack web development skills using PHP, MySQL, and Bootstrap.
