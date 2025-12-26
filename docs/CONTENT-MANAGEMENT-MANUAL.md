# Content Management Manual

A guide for website content operators with no prior WordPress experience.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Understanding the Admin Panel](#understanding-the-admin-panel)
3. [Multilingual Content (Important!)](#multilingual-content-important)
4. [Editing the Homepage](#editing-the-homepage)
5. [Managing Pages](#managing-pages)
6. [Managing Blog Posts](#managing-blog-posts)
7. [Site Settings (Footer Content)](#site-settings-footer-content)
8. [Working with Images](#working-with-images)
9. [Common Tasks](#common-tasks)
10. [Troubleshooting](#troubleshooting)

---

## Getting Started

### How to Access the Admin Panel

1. Go to your website URL and add `/wp-admin` at the end
   - Example: `https://couplescompatibility.com/wp-admin`
2. Enter your username and password
3. You will see the WordPress Dashboard

### The Easiest Way to Edit a Page

**Recommended method:** While viewing any page on your website:

1. Make sure you are logged in (you'll see a black admin bar at the top)
2. Click **"Edit Page"** in the top admin bar
3. This takes you directly to edit that specific page

---

## Understanding the Admin Panel

### Left Sidebar Menu

| Menu Item | What It's For |
|-----------|---------------|
| **Dashboard** | Overview and quick access |
| **Posts** | Blog articles (appear in "From Our Blog" section) |
| **Pages** | Static pages (Homepage, About, Privacy, etc.) |
| **Media** | All uploaded images and files |
| **Languages** | Polylang language settings |

### Key Concepts

| Term | Meaning |
|------|---------|
| **Page** | Static content (Homepage, About Us, Privacy Policy) |
| **Post** | Blog article with date, shown in blog section |
| **ACF Fields** | Custom content fields (text boxes, URLs) below the main editor |
| **Polylang** | Plugin that manages translations |

---

## Multilingual Content (Important!)

This website has **3 language versions**:
- English (EN) - default
- Russian (RU)
- Ukrainian (UK)

### How Translations Work

Every page and post has **3 separate copies** - one for each language. When you edit content, you must:

1. Edit the English version
2. Edit the Russian version
3. Edit the Ukrainian version

### How to Switch Between Language Versions

When editing a page, look at the **right sidebar**. You'll see a "Languages" box:

```
Languages
─────────────────────────────
English ● (current)
Russian ✏️  ← Click pencil to edit
Ukrainian ✏️  ← Click pencil to edit
```

- **Pencil icon (✏️)** = Click to edit that language version
- **Dot (●)** = Currently editing this version

### CRITICAL: Don't Forget to Save!

After editing EACH language version:

1. Scroll up
2. Click the blue **"Update"** button
3. Then switch to the next language and repeat

**If you don't click "Update", your changes will be lost!**

---

## Editing the Homepage

The homepage is special. It uses **ACF (Advanced Custom Fields)** instead of the regular visual editor.

### What You'll See

When editing the homepage:

1. **Visual Editor** - Empty or minimal (NOT used for homepage content)
2. **Custom Fields** - Scroll down to see tabs with all the actual content

### Homepage Content Tabs

| Tab | Contains |
|-----|----------|
| **Page Meta** | SEO title |
| **Hero Section** | Main headline and subtitle at the top |
| **About Section** | "Why This Analysis Is Different" + 3 benefits |
| **Example Report** | Sample quote from report + CTA button |
| **Pricing** | Section title (pricing cards are automatic) |
| **Reviews** | 3 customer testimonials with photos |
| **FAQ** | 5 questions and answers |
| **Footer** | (Footer content is in Site Settings - see below) |

### Editing Homepage Content

1. Go to **Pages → Front Page** (or click "Edit Page" while on homepage)
2. Scroll down past the empty visual editor
3. Click on the tab you want to edit (e.g., "Hero Section")
4. Edit the text fields
5. Click **"Update"** to save
6. **Switch to Russian version** (click pencil ✏️ in sidebar)
7. Edit the same fields with Russian text
8. Click **"Update"**
9. **Switch to Ukrainian version** and repeat

---

## Managing Pages

### Existing Pages

| Page | Purpose | Notes |
|------|---------|-------|
| **Front Page** | Homepage | Uses ACF fields (see above) |
| **About** | About us page | Regular content editor |
| **Privacy Policy** | Legal page | Regular content editor |
| **Terms of Service** | Legal page | Regular content editor |
| **Result** | Calculator results | DO NOT EDIT - contains shortcode |
| **Site Settings** | Footer content storage | DO NOT DELETE (see below) |

### Editing a Regular Page (About, Privacy, Terms)

1. Go to **Pages** in left sidebar
2. Hover over the page name → Click **"Edit"**
3. Edit the content in the visual editor
4. Click **"Update"**
5. Switch languages and translate

### Visual Editor Toolbar

| Button | What It Does |
|--------|--------------|
| **B** | Bold text |
| **I** | Italic text |
| **Link icon** | Add a link |
| **" "** | Block quote |
| **Lists** | Bullet or numbered list |
| **Add Media** | Insert an image |

---

## Managing Blog Posts

Blog posts appear on the homepage in the **"From Our Blog"** section.

### Creating a New Blog Post

1. Go to **Posts → Add New**
2. Enter the title
3. Write your article in the editor
4. On the right sidebar:
   - Set a **Featured Image** (appears as thumbnail)
   - Choose a **Category** if applicable
5. Click **"Publish"**
6. Create translations:
   - In the Languages box, click **"+"** next to Russian
   - Write the Russian version
   - Repeat for Ukrainian

### Editing Existing Posts

1. Go to **Posts → All Posts**
2. Click on the post title
3. Make your changes
4. Click **"Update"**
5. Don't forget to update all 3 language versions!

---

## Site Settings (Footer Content)

Footer content (brand name, description, email, social links, copyright) is stored in a special page.

### How to Edit Footer Content

1. Go to **Pages**
2. Find **"Site Settings"** page
3. Click **"Edit"**
4. You'll see fields for:
   - Brand Name
   - Site Description
   - Contact Email
   - Instagram URL
   - Facebook URL
   - Privacy Policy URL
   - Terms URL
   - Copyright Text
5. Edit the fields
6. Click **"Update"**
7. **Switch to Russian/Ukrainian versions** and translate

### WARNING

**DO NOT DELETE the Site Settings page!** It stores footer translations for all languages.

---

## Working with Images

### Adding Images to Content

1. While editing a page/post, place your cursor where you want the image
2. Click **"Add Media"** button above the editor
3. Either:
   - **Upload Files** - drag and drop or click to select
   - **Media Library** - choose existing image
4. Click **"Insert into page"**

### Image Best Practices

| Type | Recommended Size | Notes |
|------|------------------|-------|
| Blog featured image | 800×500 px | Appears in blog grid |
| Reviewer avatar | 150×150 px | Circular crop |
| Content images | Max 1200px wide | Will be resized |

### Editing Images in Media Library

1. Go to **Media** in left sidebar
2. Click on any image
3. You can:
   - Edit title and alt text (good for SEO)
   - Crop or rotate
   - Delete if unused

---

## Common Tasks

### Change Homepage Hero Title

1. Edit the Front Page
2. Scroll to **"Hero Section"** tab
3. Edit **"Hero Title"** field
4. Update → Switch language → Repeat

### Add a New FAQ Question

1. Edit the Front Page
2. Scroll to **"FAQ"** tab
3. Find an empty Question/Answer pair (or edit existing)
4. Enter the new Q&A
5. Update → Translate to other languages

### Change a Customer Review

1. Edit the Front Page
2. Scroll to **"Reviews"** tab
3. Find Review 1, 2, or 3
4. Edit:
   - Avatar (upload new image)
   - Name
   - Age
   - Location
   - Review text
5. Update → Translate

### Update Contact Email

1. Edit **Site Settings** page
2. Change **"Contact Email"** field
3. Update → Update other language versions

### Publish a New Blog Article

1. Posts → Add New
2. Write article with featured image
3. Publish
4. Create Russian translation (+ button)
5. Create Ukrainian translation (+ button)

---

## Troubleshooting

### "I made changes but they don't appear on the website"

1. Did you click **"Update"**?
2. Try clearing your browser cache (Ctrl+Shift+R or Cmd+Shift+R)
3. Check if you edited the correct language version

### "I can't find where to edit something"

- Homepage content → Front Page → Scroll down to ACF fields
- Footer content → Site Settings page
- Menu links → Appearance → Menus (if exists)

### "The page looks broken after I edited it"

1. Go back to edit the page
2. Click **"Revisions"** in the right sidebar
3. Select a previous version to restore

### "I accidentally deleted something important"

1. Check **Trash**:
   - Pages → Trash
   - Posts → Trash
2. Hover over the item → Click **"Restore"**

### "I need to edit content that's not in the admin"

Some text is part of the theme or plugin and cannot be edited in WordPress admin. Contact the developer for:
- Button labels in the calculator
- Error messages
- Hardcoded design elements

---

## Quick Reference Card

### Daily Tasks

| Task | Where |
|------|-------|
| Edit page | Pages → [Page Name] → Edit |
| Edit blog post | Posts → [Post Name] → Edit |
| Add blog post | Posts → Add New |
| Add image | Media → Add New |

### Remember

1. **Always click "Update"** after changes
2. **Edit all 3 language versions** (EN, RU, UK)
3. **Don't delete Site Settings page**
4. **Don't edit Result page** (contains calculator)

### Keyboard Shortcuts (in editor)

| Shortcut | Action |
|----------|--------|
| Ctrl+B (Cmd+B) | Bold |
| Ctrl+I (Cmd+I) | Italic |
| Ctrl+K (Cmd+K) | Insert link |
| Ctrl+Z (Cmd+Z) | Undo |
| Ctrl+S (Cmd+S) | Save draft |

---

## Need Help?

If you encounter issues not covered in this manual, contact the site administrator or developer.
