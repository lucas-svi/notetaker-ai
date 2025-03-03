# Notetaker AI

## 📌 Team Members

- Lucas Svirsky (33%)
- Matthew Rich (33%)
- Ford Zamore (33%)

## 🚀 About

Notetaker AI is a web app that helps users take notes using AI. It transcribes lectures, organizes notes, and creates flashcards.

## 📂 Files

- **index.html** – Main webpage
- **signup.html** – Signup webpage
- **styles.css** – Styling
- **/images/** – Contains images like the logo
- **README.md** – This file
- **LICENSE** - The MIT License under which this project is licensed by

## 🛠️ How to Run

1. **Download or clone the repo:**
   ```bash
   git clone https://github.com/lucas-svi/notetaker-ai.git
   ```
2. **Open `index.html` in your browser**
   - Double-click the file to open it.
   - Or, copy the full file path and paste it into your browser’s address bar.

## Database Setup

1. Create the database called 'app-db'
2. Create users table

```sql
CREATE TABLE users (
username VARCHAR(50) PRIMARY KEY,
email VARCHAR(50),
password VARCHAR(255)
);
```

## 📜 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.
