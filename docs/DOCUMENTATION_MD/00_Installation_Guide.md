# How to Install and Set Up AIRA-LOGIX

Follow these simple steps to get the AIRA-LOGIX assistant up and running on your computer.

## 1. What You Need (Prerequisites)
Before starting, make sure your computer has these three basic tools:
1. **PHP**: The engine that runs the website logic.
2. **Composer**: A tool that downloads the necessary PHP libraries (this includes the **Neuron AI** "brain" and Document Readers).
3. **Node.js**: A tool that handles the modern design, charts, and interactive parts.

## 2. Installation Steps

### Step A: Download the Project
Download the project files or copy them to a folder on your computer.

### Step B: Download All Tools & AI Libraries
Open your terminal (PowerShell or Command Prompt) inside the project folder and run:
```bash
composer install
```
*Note: This automatically downloads **Neuron AI**, PHPWord, and PhpSpreadsheet.*

### Step C: Download Design Tools
Next, run this command to set up the interactive design parts (like Charts and Diagrams):
```bash
npm install
```

### Step D: Set Up the "Secret Key" & AI Access
1. Look for a file named `.env.example` in the main folder.
2. Copy it and rename the copy to just `.env`.
3. Open the `.env` file and add these (note: they may not exist in `.env.example` yet):
   - `GEMINI_API_KEY=` (Required for AI extraction)
   - Optional: `GEMINI_MODEL=` and `AI_BUDGET_THRESHOLD=`
4. Run this command to create a unique security key for your site:
```bash
php artisan key:generate
```

### Step E: Prepare the Database
To set up the storage for your records, run:
```bash
php artisan migrate
```

### Step F (Optional): Create Initial Users
This repo includes seeders for initial admin accounts (for local/dev use).
```bash
php artisan db:seed
```

## 3. How to Start the System
AIRA-LOGIX needs three things running at once: the **Website**, the **Design Engine**, and the **Background Worker** (which handles the AI extraction).

You can start all three with a single command:
```bash
composer run dev
```
*Your site will be available at `http://127.0.0.1:8000`*

If you prefer to start them manually, you will need three terminal windows:
1. `php artisan serve` (Site)
2. `npm run dev` (Design)
3. `php artisan queue:listen` (The AI Background Worker)

---
