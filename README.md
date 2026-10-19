# FORMULAHUB — Formula 1 Fan & Race Management Platform

FormulaHub is a full-stack native PHP 8+ and MySQL web application designed for Formula 1 fans. It features race tracking, telemetry countdowns, driver & constructor rosters, user authentication, interactive predictions, polls, community discussion feed, and a complete admin management dashboard.

---

## Technical Stack
- **Frontend**: HTML5, CSS3 (Vanilla Motorsport Design System with Glassmorphism), Vanilla JavaScript.
- **Backend**: Native PHP 8+, PDO prepared statements, PHP Session auth, password_hash.
- **Database**: MariaDB / MySQL (`formulahub`).
- **Development Server**: XAMPP (Apache + MySQL).

---

## Setup & Installation Instructions (XAMPP)

1. **Start Apache & MySQL**:
   - Open the **XAMPP Control Panel**.
   - Start **Apache** and **MySQL** services.

2. **Project Directory**:
   - Ensure the project folder is placed at:
     ```
     C:/xampp/htdocs/formulahub/
     ```

3. **Database Import (phpMyAdmin)**:
   - Open your browser and navigate to `http://localhost/phpmyadmin/`.
   - Click **Import** tab.
   - Choose the file located at: `database/formulahub.sql`.
   - Click **Go** to create the `formulahub` database with all 11 tables and sample data.

4. **Access Application**:
   - Open your web browser and visit:
     ```
     http://localhost/formulahub/
     ```

---

## Demo Accounts

### Admin Account
- **Email**: `admin@formulahub.com`
- **Password**: `Admin@123`
- **Access**: Full access to Admin Panel (`http://localhost/formulahub/admin/`).

### User Account
- **Email**: `user@formulahub.com`
- **Password**: `User@123`
- **Access**: Fan Dashboard, predictions, voting, favorites, profile settings.

---

## Features Overview
- **Homepage**: Broadcast-style design with live JS countdown to the next Grand Prix, latest podium breakdown, featured drivers, constructors, and fan leaderboard preview.
- **Race Calendar**: Filter upcoming & completed races, track circuit layout details, and view official race results.
- **Drivers & Teams**: Detailed profiles, championship standings, wins, podiums, and career bios.
- **Favorites System**: Bookmarking for drivers, teams, and races (persisted in MySQL).
- **Predictions & Polls**: Earn fan points for voting and accurately predicting Grand Prix winners.
- **Admin Portal**: Full CRUD for Users, Drivers, Teams, Races, Race Results calculation, Comments, Polls, and Broadcast Notifications.
