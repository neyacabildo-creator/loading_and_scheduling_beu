# GitHub Repository Setup

Follow these steps to create your own GitHub repository and get a working link.

---

## Step 1: Create a New Repository on GitHub

1. Go to https://github.com/new
2. Log in to your GitHub account
3. Fill in the repository details:
   - **Repository name:** `thesis-capstone-schedule-approval`
   - **Description:** `Faculty Loading & Scheduling System with Admin Management Dashboard`
   - **Visibility:** Choose Public or Private
4. Click **Create repository**

---

## Step 2: Initialize Git Locally

Run these commands in your project root (`C:\wamp64\www\thesis_capstone\note`):

```powershell
cd C:\wamp64\www\thesis_capstone\note
git init
git add .
git commit -m "Initial commit: Faculty Loading & Scheduling System"
```

---

## Step 3: Connect to GitHub and Push

After creating the repository on GitHub, copy the HTTPS link from the green **Code** button, then run:

```powershell
git remote add origin https://github.com/YOUR-USERNAME/thesis-capstone-schedule-approval.git
git branch -M main
git push -u origin main
```

Replace `YOUR-USERNAME` with your actual GitHub username.

---

## Step 4: Get Your Repository Link

Your repository will be available at:

```
https://github.com/YOUR-USERNAME/thesis-capstone-schedule-approval
```

Copy this link and share it with others!

---

## Tips

- Use **HTTPS** for easier cloning (recommended for beginners)
- Use **SSH** if you have SSH keys configured (more secure)
- You can change repository settings anytime in GitHub repo settings