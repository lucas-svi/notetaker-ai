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

3. Create notes table

```sql
CREATE TABLE notes (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50),
note TEXT NOT NULL,
FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);
```

## phpMyAdmin

- Matthew's Picture
<img width="1728" alt="image" src="https://github.com/user-attachments/assets/f9358840-ef2c-4578-aa66-7d4488409824" />

- Ford's Picture
<img width="1440" alt="Screenshot 2025-03-06 at 2 38 22 PM" src="https://github.com/user-attachments/assets/4a7fbe6e-699b-491f-9d3d-b754a917f278" />
<img width="1440" alt="Screenshot 2025-03-06 at 2 38 42 PM" src="https://github.com/user-attachments/assets/50e97969-381b-4ae4-8daf-6f6bf509047d" />

- Lucas' Picture
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/6cec6c1a-ba06-4899-869f-d3772106d905" />


## 📜 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.
