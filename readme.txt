=== Urban Renewal Analyzer ===
Contributors: yourname
Tags: urban renewal, document analysis, AI, contracts
Requires at least: 5.8
Tested up to: 6.3
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

מערכת מתקדמת לניתוח הסכמי התחדשות עירונית באמצעות AI.

== Description ==

מערכת מקצועית לניתוח אוטומטי של הסכמי התחדשות עירונית באמצעות בינה מלאכותית. המערכת כוללת:

* ניהול לקוחות והזמנות
* העלאת קבצים מאובטחת ל-Amazon S3
* ניתוח מסמכים עם DeepSeek AI
* יצירת דוחות PDF מוגנים
* מערכת SMS ואימיילים אוטומטית
* בסיס ידע פנימי למנהלים

== Installation ==

1. העלה את הקובץ `urban-renewal-analyzer.zip` דרך תפריט 'תוספים > הוסף חדש' בוורדפרס
2. הפעל את הפלאגין דרך תפריט 'תוספים'
3. עבור ל'התחדשות עירונית > הגדרות' והגדר את ה-APDs הנדרשים
4. צור שירותים ראשונים ותבניות פרומפטים

== Configuration ==

יש להגדיר את השירותים החיצוניים הבאים:

* DeepSeek API Key
* Amazon S3 Credentials
* SMTP Settings
* SMS Provider (MSG91/Twilio/Cellact)

== Frequently Asked Questions ==

= האם נדרש ידע בתכנות? =
לא. המערכת כוללת ממשק ניהול מלא ואינה דורשת ידע בתכנות.

= כמה עולה השימוש ב-DeepSeek API? =
תלוי בנפח. עבור 30 משתמשים בחודש, העלות צפויה להיות $10-20 לחודש.

= האם הקבצים מאובטחים? =
כן. כל הקבצים נשמרים ב-Amazon S3 עם סריקת וירוסים אוטומטית.

== Changelog ==

= 1.0.0 =
* גרסה ראשונית
* מערכת ניהול הזמנות
* אינטגרציית DeepSeek AI
* יצירת PDFs
* מערכת SMS ואימיילים