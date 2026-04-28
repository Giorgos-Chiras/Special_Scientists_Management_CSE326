# Special Scientists Management Application

This web application is developed for the Cyprus University of Technology (ΤΕΠΑΚ) to streamline the recruitment and enrollment of special scientists (Ειδικοί Επιστήμονες). It automates the hiring process and integrates with the Moodle Learning Management System (LMS) via API.

The project is built using MySQL/MariaDB, PHP, HTML, CSS, and JavaScript.

**GitHub:** https://github.com/Giorgos-Chiras/Special_Scientists_Management_CSE326

---

## Project Overview

The system is divided into three core modules, each catering to different user roles:

- **Admin Module**: Enables administrators to manage users, recruitment cycles, system settings, and view statistical reports.
- **Recruitment Module**: Allows candidates, evaluators, and HR staff to manage profiles, submit and track applications, and monitor application status.
- **Enrollment Module**: Provides hired special scientists and HR staff with tools to synchronize with Moodle, control course access, and view enrollment reports.

---

## Features

- User registration and login with role-based access control (Admin, Candidate, Evaluator, HR)
- Role-specific dashboards with intuitive navigation
- User management (add, remove, update, assign roles)
- Recruitment management: configure application periods, departments, schools, courses, and evaluators
- System configuration: customize theme (logos, texts) and Moodle connection settings
- Reports and statistics with graphical visualizations (e.g., number of applications per course, Moodle access status)
- Application submission and status tracking with visual indicators
- Moodle integration:
  - Check and manage user access to LMS
  - Enable/disable course access for special scientists
  - Full synchronization and automatic sync options
- Search, filter, and sort functionalities for all data lists
- Fully responsive design for mobile and desktop devices

---

## Prerequisites

Ensure the following are installed on your system before proceeding:

| Software | Tested Version |
|----------|----------------|
| OS       | Linux (Ubuntu 24.04.1) |
| Apache   | 2.4.58 |
| MySQL    | 8.0.45 |
| PHP      | 8.3.6 |
| Git      | Any recent version |

---

## Installation & Setup

### 1. Clone the Repository

```bash
cd /var/www/html
git clone https://github.com/Giorgos-Chiras/Special_Scientists_Management_CSE326
```

If you don't have write permission to `/var/www/html`, run:

```bash
sudo chown -R $USER:$USER /var/www/html
```

### 2. Configure Database Credentials

Edit the database configuration file with your MySQL credentials:

```
/var/www/html/Special_Scientists_Management_CSE326/includes/db.php
```

### 3. Start Apache

```bash
sudo systemctl start apache2
sudo systemctl status apache2  # Verify it's running
```

### 4. Set Up the Database

Connect to MySQL:

```bash
sudo mysql
# Or, if you changed your credentials:
sudo mysql -u <user> -p
```

> If your terminal already shows `mysql>`, type `exit` first to return to the shell.

Then run the schema and seed scripts:

```bash
sudo mysql < /var/www/html/Special_Scientists_Management_CSE326/database/schema.sql
sudo mysql < /var/www/html/Special_Scientists_Management_CSE326/database/seed.sql
```

### 5. Open the Application

Visit the app in your browser at:

```
http://localhost/Special_Scientists_Management_CSE326/
```

---

## Default Test Users

The seed database includes the following pre-configured accounts:

| Role  | Email             | Password      |
|-------|-------------------|---------------|
| User  | user1@test.com    | User1Pass123  |
| User  | user2@test.com    | User2Pass123  |
| Admin | admin@test.com    | AdminPass123  |

---

## Technologies Used

- **Backend**: PHP
- **Database**: MySQL / MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **LMS Integration**: Moodle API
- **Version Control**: Git

---

## Team Members

This project was developed by three students for the course **CEI326 Web Engineering** at the Cyprus University of Technology:

| Name | Student ID | Contribution | GitHub |
|------|------------|--------------|--------|
| Giorgos Chiras | 27909 | Database, PDO, Authorization | [GitHub](https://github.com/Giorgos-Chiras) |
| Sofia Kalaitsidou | 31061 | List views | [GitHub](https://github.com/SofiaKalaitsidou) |
| Xenia Paschali | 30283 | Dashboard | [GitHub](https://github.com/XeniaPaschali) |

---

## License

This project is created for educational purposes and is not intended for commercial use.

---

For any questions or feedback, please contact the team members.
