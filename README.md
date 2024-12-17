# Muscle Memory - Fitness Connection Platform

## Overview
Muscle Memory is a comprehensive web application that connects fitness enthusiasts with professional trainers and nutritionists. The platform facilitates workout planning, nutrition consultation, and real-time communication between users and fitness professionals.

## Features

### User Management
- Multi-role authentication system (Regular Users, Trainers, Nutritionists, Admins)
- Secure login/signup with password hashing
- Profile management with customizable avatars
- Role-specific dashboards

### Professional Profiles
#### Trainers
- Detailed profile creation
- Specialization listing
- Experience and certification display
- Training session rate setting
- Custom workout plan creation
- Client management system

#### Nutritionists
- Comprehensive profile setup
- Specialty areas display
- Consultation scheduling
- Client progress tracking
- Consultation rate management

### Communication System
- Real-time chat functionality
- Client-Professional messaging
- Chat history preservation
- Active connection management

### Workout Management
- Custom workout creation
- Category-based organization
- Difficulty levels
- Equipment requirements
- Target muscle group tracking
- Workout sharing capabilities

### Connection System
- Request/Accept mechanism
- Active session tracking
- Pending request management
- Connection status monitoring

### Admin Features
- User management
- Professional verification
- Platform statistics
- Content moderation
- System monitoring

## Technical Stack
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Server**: Apache
- **Additional Libraries**: 
  - BoxIcons
  - Google Fonts (Josefin Slab)

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache web server
- mod_rewrite enabled

### Setup Instructions
1. Clone the repository:

```bash
git clone https://github.com/AndreAyiku/muscle-memory.git
```
2. CREATE DATABASE muscle_memory;

mysql -u your_username -p muscle_memory < database/schema.sql

<?php
$host = 'your_host';
$username = 'your_username';
$password = 'your_password';
$database = 'muscle_memory';

<VirtualHost *:80>
    DocumentRoot "/path/to/muscle-memory"
    ServerName muscle-memory.local
    <Directory "/path/to/muscle-memory">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>



3. Database Structure
MM_Users - User management
MM_Trainers - Trainer profiles
MM_Nutritionist - Nutritionist profiles
MM_ClientConnections - Professional-client relationships
MM_Workouts - Workout plans
MM_Messages - Chat system
## Security Features
Password hashing
Session management
SQL injection prevention
XSS protection
CSRF protection
Input validation
Prepared statements
## User Roles
# Regular User
Browse professionals
Request connections
View workout plans
Chat with connected professionals
Track progress
# Trainer
Create/manage profile
Accept/reject clients
Create workout plans
Chat with clients
Track client progress
# Nutritionist
Create/manage profile
Accept/reject clients
Provide consultations
Chat with clients
Monitor client progress
# Admin
Manage all users
Monitor platform activity
View statistics
Moderate content
System maintenance

## Applciation Structure

