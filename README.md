# Muscle Memory Fitness Platform

<p align="center">
  <img src="assests/images/dumbell.ico" alt="Muscle Memory Logo" width="200"/>
</p>

## Overview
Muscle Memory is a comprehensive web-based fitness platform that connects users with professional trainers and nutritionists. The platform facilitates workout planning, nutrition consultation, and real-time communication between clients and fitness professionals.

## Table of Contents
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Database Structure](#database-structure)
- [User Roles](#user-roles)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

## Features

### User Management
- Multi-role authentication (Client, Trainer, Nutritionist, Admin)
- Secure login/registration system
- Profile management with photo upload
- Role-specific dashboards

### Professional Features
- **Trainers:**
  - Custom profile creation
  - Workout plan management
  - Client tracking
  - Real-time chat with clients
  
- **Nutritionists:**
  - Professional profile setup
  - Client consultation management
  - Progress monitoring
  - Direct client communication

### Communication System
- Real-time chat functionality
- Client-Professional messaging
- Chat history tracking
- Active connections management

### Admin Controls
- User management
- Platform statistics
- Professional verification
- System monitoring

## Tech Stack
- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.0+
- **Database:** MySQL
- **Server:** Apache
- **Libraries:** 
  - BoxIcons
  - Google Fonts (Josefin Slab)

## Installation

### Prerequisites
```bash
- PHP 8.0+
- MySQL 5.7+
- Apache Server
- mod_rewrite enabled
```

### Database Setup
```sql
CREATE DATABASE muscle_memory;
USE muscle_memory;

-- Import the provided SQL schema
source path/to/database/schema.sql
```

## Database Configuration
```php
// filepath: /db/config.php
<?php
$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'muscle_memory';
```

## Apache Configuration
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/muscle-memory"
    ServerName muscle-memory.local
    <Directory "/path/to/muscle-memory">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Project Structure
```plaintext
muscle-memory/
├── actions/
│   ├── login_user.php
│   ├── logout.php
│   └── register_user.php
├── admin/
│   ├── admin_dashboard.php
│   ├── trainer_dashboard.php
│   └── nutritionist_dashboard.php
├── assets/
│   ├── images/
│   ├── css/
│   └── js/
├── db/
│   └── config.php
├── includes/
│   └── header.php
├── view/
│   ├── chat.php
│   ├── profile.php
│   └── workouts.php
└── index.php
```
## Database Structure

### Core Tables
- **MM_Users**: User management and authentication
- **MM_Trainers**: Professional trainer profiles
- **MM_Nutritionist**: Professional nutritionist profiles
- **MM_ClientConnections**: Professional-client relationship management
- **MM_Workouts**: Workout plans and exercises
- **MM_Messages**: Real-time chat system

## User Roles

### Client
- Browse professionals
- Request connections
- View workouts
- Chat with professionals
- Track progress

### Trainer
- Manage profile
- Accept/reject clients
- Create workouts
- Client communication
- Progress tracking

### Nutritionist
- Profile management
- Client acceptance
- Consultation handling
- Client messaging
- Progress monitoring

### Admin
- User management
- Statistics tracking
- Content moderation
- System administration

## Security Features
- Password hashing using modern algorithms
- Secure session management
- Protection against SQL injection
- XSS (Cross-Site Scripting) prevention
- Comprehensive input validation
- Prepared SQL statements
- CSRF token implementation

## Design Features
- **Responsive Layout**: Adapts to all screen sizes
- **Dark Theme**: Easy on the eyes, modern aesthetic
- **Animated Elements**: Smooth transitions and interactions
- **Modern UI**: Clean and intuitive interface
- **User-Friendly Navigation**: Clear menu structure

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Author
### Andre Ayiku
- **GitHub**: [@andreayiku](https://github.com/andreayiku)
- **Email**: andreayiku@gmail.com

## Acknowledgments
- **UI Resources**:
  - [BoxIcons](https://boxicons.com/) - Comprehensive icon library
  - [Google Fonts](https://fonts.google.com/) - Josefin Slab typography
- **Community Support**:
  - Stack Overflow community for technical guidance
  - GitHub community for project feedback and inspiration
