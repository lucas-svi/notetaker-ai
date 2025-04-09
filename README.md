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

## 🌐 How to Access the Website
- We decided to host the website on Lucas' personal website, https://svirsky.dev/apps/notetaker-ai, since InfinityFree was giving us issues with the free usage quota.

## 🛠️ How to Run Locally

1. **Download or clone the repo:**
   ```bash
   git clone https://github.com/lucas-svi/notetaker-ai.git
   ```
2. **Open `index.html` in your browser**
   - Double-click the file to open it.
   - Or, copy the full file path and paste it into your browser’s address bar.
  
## For Mobile

1. **Make Necessary Adjustments**
   
  Change IP address to your IP address in mobile/api.js
  
  baseURL: "http://    **IP**    /notetaker-ai/backend/index.php"
  
  While here, you can also change the base url if your folder structure looks different.
  If you do this, also go to backend/index.php and update $at_start in line to be the correct number.
  Currently it is set to 3 for the amount of arguments after localhost: /notetaker-ai/backend/index.php
  
2. **Run Backend Setup**

   This setup is next in the README

3. **Run Mobile Frontend**

   Run
   ```bash
   cd frontend/mobile
   npm i
   npm run android
   ```
   while android studio is open

## Database Setup
1. Make sure you have XAMPP downloaded.
2. Run Apache and MySQL through XAMPP.
3. Navigate to localhost/phpmyadmin.
4. Create the database called 'app_db'.
5. Create the users table.

```sql
CREATE TABLE users (
username VARCHAR(50) PRIMARY KEY,
email VARCHAR(50),
password VARCHAR(255)
);
```

6. Create the notes table

```sql
CREATE TABLE notes (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50),
note TEXT NOT NULL,
FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);
```

## phpMyAdmin & Postman

- Matthew's Picture
<img width="1728" alt="image" src="https://github.com/user-attachments/assets/f9358840-ef2c-4578-aa66-7d4488409824" />
<img width="834" alt="image" src="https://github.com/user-attachments/assets/6f9a779d-91c1-4a4a-8054-834ef92e47bc" />
<img width="849" alt="image" src="https://github.com/user-attachments/assets/36560a02-d902-43e1-bb53-a8baaec824c1" />

- Ford's Picture
<img width="1440" alt="Screenshot 2025-03-06 at 2 38 22 PM" src="https://github.com/user-attachments/assets/4a7fbe6e-699b-491f-9d3d-b754a917f278" />
<img width="1440" alt="Screenshot 2025-03-06 at 2 38 42 PM" src="https://github.com/user-attachments/assets/50e97969-381b-4ae4-8daf-6f6bf509047d" />
<img width="1440" alt="Screenshot 2025-03-30 at 4 41 25 PM" src="https://github.com/user-attachments/assets/fe247d47-2fe5-4c3e-9dea-9ed3f465ed3d" />
<img width="1438" alt="Screenshot 2025-04-08 at 8 50 06 PM" src="https://github.com/user-attachments/assets/5a986d9f-df5e-4033-b9fe-3d04c0579681" />


- Lucas' Picture
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/6cec6c1a-ba06-4899-869f-d3772106d905" />


## 📜 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.
