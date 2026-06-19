Build an MVP web application called "MTGHub PH".

Goal:
Create a Philippine-focused Magic: The Gathering platform for collection tracking, price tracking, buylists, and marketplace listings.

Technology Stack:
- PHP 8.x
- MySQL/MariaDB
- Apache
- XAMPP-compatible
- MVC architecture
- PDO for database access
- Bootstrap 5 for UI
- JavaScript/jQuery only when needed
- No Node.js
- No Next.js
- No TypeScript
- No PostgreSQL
- No Prisma
- No Docker
- No Composer packages unless absolutely necessary

Hosting Requirement:
- Must run locally using XAMPP
- Project folder must be deployable inside htdocs
- Database must be importable using phpMyAdmin
- Include an SQL schema file
- Include installation instructions in README.md

Core Modules:

1. User Accounts
- Register/login/logout
- PHP session-based authentication
- User profile
- Username
- City/province in the Philippines

2. Card Database
- Store MTG card records
- Fields:
  - card name
  - set name
  - collector number
  - rarity
  - color
  - type
  - image URL
  - Scryfall ID
- Add manual card entry for admin
- Add card search and filters

3. Collection Tracker
- Users can add cards to their collection
- Fields:
  - quantity
  - condition
  - language
  - foil/non-foil
  - acquisition price
  - notes
- Show estimated collection value

4. Philippine Price Tracker
- Manual price entry first
- Fields:
  - card ID
  - source name
  - currency
  - price
  - converted PHP price
  - date captured
- Show price history per card

5. Marketplace Listings
- Users can list cards for sale
- Fields:
  - card
  - quantity
  - condition
  - price PHP
  - seller location
  - delivery options
  - status
- Status options:
  - active
  - reserved
  - sold
  - cancelled
- No payment integration yet

6. Buylist
- Users can add wanted cards
- Show matching marketplace listings

7. Admin Panel
- Manage users
- Manage cards
- Manage listings
- Manage price entries
- Hide suspicious listings

Database Tables:
Create MySQL tables for:
- users
- cards
- collections
- price_history
- listings
- wishlist_items
- buylist_offers

Folder Structure:
Use clean MVC structure:

/app
  /controllers
  /models
  /views
/config
/public
/assets
/database

Requirements:
- Mobile responsive
- Clean Bootstrap interface
- Secure password hashing
- Prepared statements using PDO
- Basic input validation
- Basic role system: admin and user
- README with setup guide
- SQL file for database import
- Sample admin account

Important:
Keep version 1 simple and functional. Do not copy Hareruya branding, layout, or content. Build an original Philippine-focused MTG platform.
