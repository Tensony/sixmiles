# Sixmiles Database Manager

A CodeIgniter-based web application to manage local MySQL/MariaDB database tables with built-in real-time synchronization to **Supabase** and a silent one-click Desktop launcher.

---

## 🚀 Features

1. **Local Database Editing & Management**: Add, update, and delete records dynamically using a clean web interface.
2. **Supabase Real-Time Sync**: Automatically synchronizes all database modifications (`INSERT`, `UPDATE`, `DELETE`) to your remote Supabase PostgREST tables.
3. **Silent Desktop Launcher**: Boots up local XAMPP services silently in the background and launches the web app in the default browser without leaving visible command prompt windows open.
4. **Advanced Query & Security Filtering**: Built-in column filtering filters data structures dynamically before sending requests to Supabase to prevent mismatched column errors.

---

## 🛠️ Installation & Setup

### 1. Configure Supabase Sync

Open **`application/config/supabase.php`** and configure your credentials:

```php
$config['supabase_sync_enabled'] = TRUE;
$config['supabase_url'] = 'https://your-project-id.supabase.co/rest/v1/';
$config['supabase_key'] = 'your-anon-or-service-role-key';

// List of databases and tables to synchronize (empty array syncs all)
$config['supabase_synced_tables'] = array();
```

### 2. Desktop Launcher Setup (Windows)

To create a clean double-clickable launcher on a new computer:

1. Open the project folder `C:\xampp\htdocs\sixmiles\`.
2. Right-click on **`Launch_App.vbs`** -> **Send to** -> **Desktop (create shortcut)**.
3. Go to the Desktop, right-click the shortcut, and select **Properties**.
4. Under the **Shortcut** tab, click **Change Icon...**, browse to `C:\xampp\htdocs\sixmiles\images\favicon.ico`, and select it.
5. Rename the shortcut to **Sixmiles App**.

---

## 📈 Monitoring & Logs

Synchronization details, including request payloads, response statuses, and errors (e.g. `HTTP 201 Created` or `HTTP 400 Bad Request`), are recorded in:

* **File**: `supabase_sync_log.txt` (located in the project root)

---

## 📦 Project Structure

* **`Launch_App.bat`**: Startup script to check and start local XAMPP and load the URL.
* **`Launch_App.vbs`**: Silent VBScript wrapper to execute the batch launcher completely hidden.
* **`application/config/supabase.php`**: Credentials and synchronization settings.
* **`application/helpers/supabase_helper.php`**: cURL wrapper to execute the REST operations.
* **`application/models/dbmodel.php`**: Modified model file containing synchronization triggers inside `newRecord()`, `updateRecord()`, and `deleteRecord()`.
