=== Bulk Date Update ===
Contributors: wplove
Tags: posts, update, date, bulk, seo, google, pages, modified date
Donate link: https://wplove.co
Requires at least: 5.0
Tested up to: 6.7.2
Requires PHP: 8.0
Stable tag: 1.0
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Change the post update date for all posts and pages in one click to improve SEO and keep your blog looking fresh and active.

== Description ==
Bulk Date Update allows you to quickly update the dates of your WordPress content, including posts, pages, custom post types, and comments. This optimization helps search engines recognize your content as fresh and relevant, potentially improving your search rankings.

#### How It Works
1. Select which content type you want to update (posts, pages, custom post types, or comments)
2. Choose a date range or time period to distribute the dates
3. Set specific filtering options (categories, tags, specific pages, etc.)
4. Select whether to update the published date, modified date, or both
5. Click update and all selected content gets random dates within your chosen range

#### Why Update Content Dates?
Search engines like Google prioritize fresh content in their rankings. When your posts appear outdated, they may receive lower visibility in search results. Regular date updates signal to search engines that your content remains relevant and up-to-date, potentially improving your SEO performance.

#### Key Features
* Bulk update **Posts** dates with category and tag filtering
* Bulk update **Pages** dates with specific page selection
* Bulk update **Any Custom Post Type** with taxonomy filtering 
* Bulk update **Comments** dates for added realism
* **Random distribution** of dates for natural appearance
* Custom date range selection for precise control
* Option to update published date, modified date, or both
* Dedicated admin menu for quick access
* Optimized performance for sites with thousands of posts

#### Support
* Community support via [wplove.co](https://wplove.co)
* Premium support available for customization

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/bulk-date-update` directory, or install directly through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Access the plugin via the "Bulk Date Update" menu in your admin sidebar
4. Select your content type, configure your options, and click update

== Frequently Asked Questions ==
= Is updating post dates good for SEO? =
Yes, according to SEO experts, updating post dates can help improve your search rankings by signaling to search engines that your content is fresh and relevant. However, it's recommended to actually improve or update your content when you update dates for maximum benefit.

= Will this plugin slow down my website? =
No, the plugin is designed for performance and processes content in batches, reducing server load. It's safely optimized for sites with thousands of posts.

= Can I update specific posts only? =
Absolutely! You can filter posts by categories, tags, or specific taxonomies. For pages, you can select individual pages to update.

= Does this affect post content? =
No, this plugin only changes the date metadata of your posts, it doesn't modify any post content or affect your permalinks.

= How often should I update post dates? =
For optimal SEO benefits without appearing manipulative, we recommend updating post dates every 3-4 weeks, and ideally after making actual content improvements.

= Will search engines penalize me for using this? =
When used responsibly (not daily), search engines typically won't penalize date updates. However, it's always recommended to follow this practice in moderation and in combination with actual content updates.

= Is the plugin compatible with WordPress multisite? =
Yes, the plugin works on multisite installations, but must be activated on each site individually.

= Does it work with page builders like Elementor, Divi, etc.? =
Yes, the plugin updates WordPress core date fields that are independent of page builders.

== Screenshots ==
1. Posts date update screen with category filtering
2. Pages date update screen
3. Custom post type date update screen
4. Comment date update screen

== Changelog ==

= 1.0 =
* Complete plugin rebranding and code modernization
* Added dedicated admin menu for easier access
* Implemented batch processing for improved performance with large sites
* Enhanced security with proper data sanitization and validation
* Added memory management to handle sites with thousands of posts
* Improved UI with modern styling and better user feedback
* Fixed potential SQL injection vulnerabilities with prepared statements
* Added comprehensive validation for form inputs
* Improved error handling and user feedback
* Updated taxonomies handling for better compatibility
* Added null checks to prevent PHP warnings
* PHP 8.0-8.4 compatibility with type declarations
* WordPress 6.7.2 and 6.8 compatibility updates
* Fixed date range picker issues on some WordPress themes
* Added confirmation message with count of updated items
* Optimized database queries for better performance
* Implemented proper escaping for all output
* Updated UI with accessible color schemes
* Added responsive design improvements for mobile admin

== Upgrade Notice ==
= 1.0 =
Major update with improved performance, security enhancements, modern UI, and compatibility with latest WordPress and PHP versions.
