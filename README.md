

# TaskMaster - Task Management Web Application

TaskMaster is a robust, full-featured task management platform built with PHP and MySQL, designed to streamline personal and team productivity. It offers a comprehensive suite of tools for user management, task organization, and performance tracking, all wrapped in a modern, user-friendly interface. Whether you're managing daily to-dos or complex projects, TaskMaster provides intuitive features like task prioritization, tagging, and progress analytics, with added personalization options such as dark mode and custom task sorting.

This README provides detailed instructions for setting up, testing, and deploying TaskMaster, with a focus on using **XAMPP** for local development. It also includes advanced configuration tips, troubleshooting guidance, and best practices for production environments.

---

## 🌟 Key Features

TaskMaster is packed with features to enhance productivity and user experience:

- **User System**:
  - Secure user registration with unique username and email validation.
  - Login with session management and last-login tracking.
  - Password recovery via a simulated email-based reset flow.

- **Task Management**:
  - Create, edit, and delete tasks with titles, descriptions, due dates, and priorities (High, Medium, Low).
  - Categorize tasks with customizable tags for easy filtering.
  - Mark tasks as completed or recurring for ongoing projects.
  - Drag-and-drop sorting to prioritize tasks, with persistent order storage.

- **Data Visualization**:
  - Interactive progress ring showing task completion percentages.
  - Analytics dashboard summarizing task status, priorities, and overdue items.
  - Filter tasks by tags, priority, or completion status for quick insights.

- **Personalization**:
  - Toggle between light and dark modes for comfortable viewing.
  - Custom task ordering via drag-and-drop or manual sort options.
  - Responsive design for seamless use on desktop and mobile devices.

- **Security**:
  - CSRF protection for all form submissions.
  - Password hashing using PHP’s `password_hash()` for secure storage.
  - Session management with configurable timeouts and secure cookies.

---

## 🛠️ Environment Requirements

TaskMaster is optimized for local development using **XAMPP**, which bundles Apache, PHP, and MySQL (MariaDB) into a single, easy-to-use package. Below are the requirements:

- **XAMPP Version**: ≥ 8.1.6
  - Includes PHP 8.1+ (supports modern features like enums and nullsafe operators).
  - Includes MariaDB 10.4+ (compatible with MySQL syntax).
- **Operating System**:
  - Windows 10/11
  - macOS 10.15+ (Catalina or later)
  - Linux (Ubuntu 20.04+ or equivalent)
- **Browser**:
  - Latest versions of Chrome, Firefox, Edge, or Safari.
  - JavaScript and cookies must be enabled.
- **Disk Space**: ~100 MB for XAMPP and project files.
- **RAM**: ≥ 2 GB (4 GB recommended for smooth performance).

---

## 🚀 Quick Setup (XAMPP Version)

Follow these steps to set up TaskMaster locally using XAMPP.

### 1. Install XAMPP
1. **Download XAMPP**:
   - Visit [apachefriends.org](https://www.apachefriends.org/download.html) and download XAMPP for your operating system.
   - Choose the version with PHP 8.1+ (e.g., XAMPP 8.1.6 or later).

2. **Install XAMPP**:
   - Windows: Run the installer and select Apache and MySQL components.
   - macOS: Mount the `.dmg` file and drag XAMPP to Applications.
   - Linux: Run the installer with `sudo` and follow the prompts.

3. **Start Services**:
   - Open the XAMPP Control Panel.
   - Start **Apache** and **MySQL** services.
   - Verify by visiting `http://localhost` in your browser (should show XAMPP dashboard).

### 2. Set Up Project
1. **Clone the Repository**:
   - Open a terminal and navigate to XAMPP’s web root:
     ```bash
     # Windows
     cd C:\xampp\htdocs
     # macOS
     cd /Applications/XAMPP/htdocs
     # Linux
     cd /opt/lampp/htdocs
     ```
   - Clone the TaskMaster repository:
     ```bash
     git clone https://github.com/JefferyLaw/comp3421.git taskmaster
     ```
   - Alternatively, download the ZIP file from GitHub and extract it to the `htdocs` directory, renaming the folder to `taskmaster`.

2. **Verify Directory**:
   - Ensure the project is in:
     - Windows: `C:\xampp\htdocs\taskmaster`
     - macOS: `/Applications/XAMPP/htdocs/taskmaster`
     - Linux: `/opt/lampp/htdocs/taskmaster`
   - The directory should contain files like `login.php`, `register.php`, and `task.php`.

### 3. Create Database
1. **Access phpMyAdmin**:
   - Open your browser and go to `http://localhost/phpmyadmin`.
   - Log in with default credentials:
     - Username: `root`
     - Password: (leave blank, as XAMPP’s default MySQL `root` user has no password).

2. **Create Database**:
   - Click **New** in the left sidebar.
   - Enter:
     - Database name: `task_management`
     - Collation: `utf8mb4_general_ci` (for Unicode support).
   - Click **Create**.

3. **Set Up Tables**:
   - Select the `task_management` database.
   - Go to the **SQL** tab and execute the following SQL to create the required tables:
     ```sql
     CREATE TABLE users (
         id INT PRIMARY KEY AUTO_INCREMENT,
         username VARCHAR(50) UNIQUE NOT NULL,
         email VARCHAR(100) UNIQUE NOT NULL,
         password VARCHAR(255) NOT NULL,
         last_login DATETIME,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     );

     CREATE TABLE tasks (
         id INT PRIMARY KEY AUTO_INCREMENT,
         user_id INT NOT NULL,
         title VARCHAR(255) NOT NULL,
         description TEXT,
         due_date DATETIME NOT NULL,
         priority ENUM('High','Medium','Low') DEFAULT 'Medium',
         tags VARCHAR(100),
         completed TINYINT(1) DEFAULT 0,
         sort_order INT,
         FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
     );
     ```
   - Verify that the `users` and `tasks` tables appear in the database.

### 4. Configure Database Connection
TaskMaster connects to the MySQL database using PHP’s `mysqli` extension. Update the database credentials in all relevant PHP files (e.g., `task.php`, `login.php`, `register.php`).

1. **Locate Connection Code**:
   - Open each PHP file in a text editor (e.g., VS Code).
   - Look for the database connection code (typically around line 13):
     ```php
     $conn = new mysqli("localhost", "root", "", "task_management");
     ```

2. **Verify Credentials**:
   - XAMPP’s default MySQL credentials are:
     - Host: `localhost`
     - Username: `root`
     - Password: (empty, i.e., `""`)
     - Database: `task_management`
   - If you set a password for the `root` user during MySQL setup, update the password field (e.g., `"TaskM@n2025"`).

3. **Example Update**:
   - If using default credentials, ensure:
     ```php
     $conn = new mysqli("localhost", "root", "", "task_management");
     ```
   - If using a custom password:
     ```php
     $conn = new mysqli("localhost", "root", "TaskM@n2025", "task_management");
     ```

4. **Save Changes**:
   - Save all modified PHP files.
   - Ensure files are in the `taskmaster` directory.

### 5. Launch Application
1. **Start XAMPP Services**:
   - Ensure Apache and MySQL are running in the XAMPP Control Panel.

2. **Access TaskMaster**:
   - Open your browser and navigate to:
     ```
     http://localhost/taskmaster/login.php
     ```
   - You should see the TaskMaster login page.

3. **Initial Login**:
   - Since no users exist yet, go to `http://localhost/taskmaster/register.php` to create an account.
   - After registering, log in with your credentials.

---

## 📝 Testing Guide

TaskMaster includes a range of features to test, ensuring functionality and user experience. Below are basic and advanced test cases.

### Basic Tests
1. **User Registration**:
   - Navigate to `http://localhost/taskmaster/register.php`.
   - Enter:
     - Username: `testuser`
     - Email: `test@example.com`
     - Password: `Test1234!`
   - Submit the form.
   - **Expected**: Redirect to `login.php` with a success message.

2. **User Login**:
   - Go to `http://localhost/taskmaster/login.php`.
   - Enter the registered credentials.
   - **Expected**: Redirect to the dashboard (`index.php`) with a task list.

3. **Task Creation**:
   - Log in and click “Create Task”.
   - Enter:
     - Title: `Test Task`
     - Description: `This is a test task`
     - Due Date: `2025-04-25 10:00`
     - Priority: `High`
     - Tags: `work, urgent`
   - Submit.
   - **Expected**: Task appears in the list, with correct details.

4. **Dark Mode Toggle**:
   - On the dashboard, click the moon icon (top-right).
   - **Expected**: Interface switches to dark theme; toggle again to revert.

### Advanced Tests
1. **Password Reset Flow**:
   - Visit `http://localhost/taskmaster/forgot_password.php`.
   - Enter a registered username (`testuser`).
   - Submit and follow the simulated email confirmation process.
   - **Expected**: Password reset link (simulated) allows setting a new password.

2. **Drag-and-Drop Sorting**:
   - Create multiple tasks.
   - Drag tasks to reorder them in the list.
   - Refresh the page.
   - **Expected**: Task order persists in the database.

3. **Task Filtering**:
   - Add tasks with different tags (e.g., `work`, `personal`).
   - Use the filter dropdown to show only `work` tasks.
   - **Expected**: Only tasks with the `work` tag are displayed.

4. **Progress Analytics**:
   - Complete several tasks by marking them as done.
   - Check the progress ring on the dashboard.
   - **Expected**: Ring updates to reflect the percentage of completed tasks.

---

## 🔧 Troubleshooting

Below are common issues and their solutions:

| **Issue**                              | **Solution**                                                                 |
|---------------------------------------|-----------------------------------------------------------------------------|
| **Database Connection Failed**         | Ensure MySQL is running in XAMPP; verify credentials in PHP files.           |
| **“404 Not Found” Error**              | Confirm project files are in `htdocs/taskmaster`; check URL path.            |
| **Tasks Not Saving**                   | Check `task_management` database and `tasks` table; ensure `user_id` is set. |
| **Dark Mode Not Working**              | Ensure JavaScript is enabled in the browser; check `script.js` for errors.   |
| **CSRF Token Mismatch**                | Clear browser cookies; ensure session is active and forms include CSRF token.|

**Additional Debugging**:
- Check PHP errors:
  ```bash
  tail -f /xampp/logs/php_error_log
  ```
- Check MySQL logs:
  ```bash
  tail -f /xampp/mysql/data/mysql.log
  ```

---

## 🔐 Security Recommendations (Production)

For deploying TaskMaster in a production environment, follow these best practices:

1. **Secure MySQL**:
   - Set a strong password for the `root` user:
     ```bash
     mysql -u root
     ALTER USER 'root'@'localhost' IDENTIFIED BY 'StrongP@ssw0rd';
     ```
   - Create a dedicated MySQL user for TaskMaster:
     ```sql
     CREATE USER 'taskmaster'@'localhost' IDENTIFIED BY 'SecureP@ss2025';
     GRANT ALL PRIVILEGES ON task_management.* TO 'taskmaster'@'localhost';
     FLUSH PRIVILEGES;
     ```
   - Update PHP files with new credentials:
     ```php
     $conn = new mysqli("localhost", "taskmaster", "SecureP@ss2025", "task_management");
     ```

2. **PHP Configuration**:
   - Enable secure session settings in `php.ini`:
     ```ini
     session.cookie_httponly = 1
     session.cookie_secure = 1
     session.use_strict_mode = 1
     ```
   - Set `display_errors = Off` for production:
     ```ini
     display_errors = Off
     log_errors = On
     ```

3. **Apache Configuration**:
   - Enable HTTPS with an SSL certificate.
   - Restrict phpMyAdmin access:
     ```apache
     <Directory "/xampp/phpmyadmin">
         Require local
     </Directory>
     ```

4. **Regular Backups**:
   - Schedule daily database backups:
     ```bash
     mysqldump -u root -p task_management > /path/to/backup.sql
     ```
   - Store backups securely offsite.

5. **Input Validation**:
   - TaskMaster includes CSRF protection, but ensure all user inputs are sanitized to prevent SQL injection or XSS attacks.

---

## ⚙️ Advanced Configuration

### Customizing TaskMaster
- **Add New Task Fields**:
  - Modify the `tasks` table to include fields like `category`:
    ```sql
    ALTER TABLE tasks ADD category VARCHAR(50);
    ```
  - Update `task.php` and frontend forms to handle the new field.

- **Extend Analytics**:
  - Add a chart library (e.g., Chart.js) to visualize task trends:
    ```html
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    ```
  - Query task data and render charts in `index.php`.

- **Email Notifications**:
  - Integrate PHPMailer for password reset and task reminders:
    ```bash
    composer require phpmailer/phpmailer
    ```
  - Configure SMTP in `forgot_password.php`.

### Performance Optimization
- **Database Indexing**:
  - Add indexes for frequent queries:
    ```sql
    CREATE INDEX idx_user_id ON tasks(user_id);
    CREATE INDEX idx_due_date ON tasks(due_date);
    ```
- **Caching**:
  - Use a caching layer (e.g., Memcached) for session data:
    ```bash
    sudo apt install memcached
    ```

---

## 📜 Deployment to Production

To deploy TaskMaster on a production server (e.g., Ubuntu with Apache and MySQL):

1. **Set Up Server**:
   - Install Apache, PHP, and MySQL:
     ```bash
     sudo apt update
     sudo apt install apache2 php php-mysql mysql-server
     ```
   - Secure MySQL:
     ```bash
     sudo mysql_secure_installation
     ```

2. **Deploy Files**:
   - Copy the `taskmaster` directory to `/var/www/html/taskmaster`.
   - Set permissions:
     ```bash
     sudo chown -R www-data:www-data /var/www/html/taskmaster
     sudo chmod -R 755 /var/www/html/taskmaster
     ```

3. **Configure Database**:
   - Create the `task_management` database and tables as in Step 3 of the setup.
   - Update PHP files with production database credentials.

4. **Configure Apache**:
   - Create a virtual host:
     ```bash
     sudo nano /etc/apache2/sites-available/taskmaster.conf
     ```
     ```apache
     <VirtualHost *:80>
         ServerName taskmaster.example.com
         DocumentRoot /var/www/html/taskmaster
         <Directory /var/www/html/taskmaster>
             Options -Indexes +FollowSymLinks
             AllowOverride All
             Require all granted
         </Directory>
         ErrorLog ${APACHE_LOG_DIR}/taskmaster_error.log
         CustomLog ${APACHE_LOG_DIR}/taskmaster_access.log combined
     </VirtualHost>
     ```
   - Enable the site:
     ```bash
     sudo a2ensite taskmaster
     sudo systemctl reload apache2
     ```

5. **Enable HTTPS**:
   - Use Let’s Encrypt for a free SSL certificate:
     ```bash
     sudo apt install certbot python3-certbot-apache
     sudo certbot --apache -d taskmaster.example.com
     ```

---

## 🤝 Contributing

Contributions to TaskMaster are welcome! To contribute:

1. **Fork the Repository**:
   - Click “Fork” on [github.com/JefferyLaw/comp3421](https://github.com/JefferyLaw/comp3421).

2. **Clone Your Fork**:
   ```bash
   git clone https://github.com/<your-username>/comp3421.git
   cd comp3421
   ```

3. **Create a Feature Branch**:
   ```bash
   git checkout -b feature/your-feature
   ```

4. **Make Changes**:
   - Implement your feature or bug fix.
   - Follow coding standards (e.g., PSR-12 for PHP).

5. **Commit and Push**:
   ```bash
   git commit -m "Add your feature description"
   git push origin feature/your-feature
   ```

6. **Open a Pull Request**:
   - Go to the original repository and create a PR.
   - Describe your changes and reference any related issues.

**Contribution Ideas**:
- Add multi-language support.
- Implement user roles (e.g., admin, editor).
- Enhance analytics with exportable reports.

---

## 📞 Support

For issues or questions:
- **Open an Issue**: [github.com/JefferyLaw/comp3421/issues](https://github.com/JefferyLaw/comp3421/issues).
- **Email**: support@taskmaster.com.
- **Community**: Join our Discord server (link in repository).

---

## 📜 License

TaskMaster is licensed under the [MIT License](LICENSE). You are free to use, modify, and distribute the code, provided you include the original copyright notice.

---

## 🙏 Acknowledgments

- **XAMPP**: For providing an easy-to-use development environment.
- **PHP Community**: For robust libraries and documentation.
- **Contributors**: Thanks to all who test, report bugs, and submit PRs.

---

💡 **Get Started Today**  
TaskMaster is your go-to solution for organized task management. Set it up in minutes with XAMPP and take control of your productivity!

