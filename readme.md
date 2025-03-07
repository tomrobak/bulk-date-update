# Bulk Date Update

Bulk Date Update allows you to quickly update the dates of your WordPress content, including posts, pages, custom post types, and comments. This optimization helps search engines recognize your content as fresh and relevant, potentially improving your search rankings.

## Description

### How It Works

1. Select which content type you want to update (posts, pages, custom post types, or comments)
2. Choose a date range or time period to distribute the dates
3. Set specific filtering options (categories, tags, specific pages, etc.)
4. Select whether to update the published date, modified date, or both
5. Click update and all selected content gets random dates within your chosen range

### Why Update Content Dates?

Search engines like Google prioritize fresh content in their rankings. When your posts appear outdated, they may receive lower visibility in search results. Regular date updates signal to search engines that your content remains relevant and up-to-date, potentially improving your SEO performance.

### Key Features

- Bulk update **Posts** dates with category and tag filtering
- Bulk update **Pages** dates with specific page selection
- Bulk update **Any Custom Post Type** with taxonomy filtering 
- Bulk update **Comments** dates for added realism
- **Random distribution** of dates for natural appearance
- **Modern date and time pickers** with intuitive presets
- **Complete history tracking** of all date updates
- **Restore previous dates** with a single click
- **Customizable history retention** (7, 14, 30, or 60 days)
- Completely redesigned UI for better user experience
- One-click presets for common time periods (Today, Last Week, Last Month)
- Option to update published date, modified date, or both
- Dedicated admin menu for quick access
- Optimized performance with minimal page reloads
- Customizable admin interface with selectable tabs
- Ultra-fast tab switching for better user experience

### Support

- Community support via [wplove.co](https://wplove.co/community/space/plugins-themes/home)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/bulk-date-update` directory, or install directly through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Access the plugin via the "Bulk Date Update" menu in your admin sidebar
4. Select your content type, configure your options, and click update

## Frequently Asked Questions

### Is updating post dates good for SEO?

Yes, according to SEO experts, updating post dates can help improve your search rankings by signaling to search engines that your content is fresh and relevant. However, it's recommended to actually improve or update your content when you update dates for maximum benefit.

### Will this plugin slow down my website?

No, the plugin is designed for performance and processes content in batches, reducing server load. It's safely optimized for sites with thousands of posts.

### Can I update specific posts only?

Absolutely! You can filter posts by categories, tags, or specific taxonomies. For pages, you can select individual pages to update.

### Does this affect post content?

No, this plugin only changes the date metadata of your posts, it doesn't modify any post content or affect your permalinks.

### How often should I update post dates?

For optimal SEO benefits without appearing manipulative, we recommend updating post dates every 3-4 weeks, and ideally after making actual content improvements.

### Can I choose specific dates and times for my date updates?

Yes! Version 1.4 introduces a fully redesigned date and time selection interface with intuitive presets. Select from common ranges like "Today," "Last 7 Days," or "Last Month" with a single click, or use the modern calendar interface for custom selections.

### Will search engines penalize me for using this?

When used responsibly (not daily), search engines typically won't penalize date updates. However, it's always recommended to follow this practice in moderation and in combination with actual content updates.

### Can I customize which tabs appear in the interface?

Yes! We've added a Settings tab that allows you to choose which tabs appear in the interface. This helps streamline your workflow by showing only the content types you need.

### Is the plugin compatible with WordPress multisite?

Yes, the plugin works on multisite installations, but must be activated on each site individually.

### Does it work with page builders like Elementor, Divi, etc.?

Yes, the plugin updates WordPress core date fields that are independent of page builders.

### Can I track the history of date changes?

Yes! Version 1.5.0 introduces comprehensive history tracking. You can see all date changes, including the previous and new dates, and even restore previous dates with a single click if needed.

## Changelog

### 1.5.0
**NEW:**
- Added complete history tracking for all date updates
- Added new History tab to view and manage all date changes
- Added customizable history retention settings (7, 14, 30, or 60 days)
- Added Bootstrap-styled buttons and form elements for better visual consistency
- Added modern card-based history interface for enhanced mobile experience
- Added AJAX-based record removal after successful date restore
- Added infinite scroll pagination for smoother browsing of large history sets
- Added detailed update logging with post titles and links
- Added sorting options for history records by previous date, new date, or update time

**IMPROVED:**
- Improved history filters with full-width flex layout and 24px spacing
- Improved post title display with proper word wrapping (no hyphenation)
- Improved responsive design with card-based layout for all screen sizes
- Enhanced post type display to show proper names instead of slugs
- Enhanced history interface with modern card-based design
- Optimized database queries for maximum performance
- Implemented card-based UI to eliminate horizontal scrolling on mobile
- Enhanced filtering with customizable sort order and direction

**FIXED:**
- Fixed navigation issues to maintain tab visibility across all views
- Fixed column overlap issues in history table for long post titles
- Fixed tablet and mobile display issues with optimized layout

### 1.4.8
- Fixed field spacing in date and time pickers for optimal layout
- Adjusted flex values to prevent fields from being too far apart
- Made date range container full width to match time range container
- Improved consistency between date and time range interfaces
- Added max-width to input wrappers to ensure proper alignment
- Optimized layout calculations for better visual balance

### 1.4.7
- Fixed critical date format errors that caused "Invalid date provided" errors
- Improved date conversion handling with robust error prevention
- Optimized start and end field spacing to exactly 24px for better alignment
- Improved field container padding and spacing for better UI
- Enhanced error handling with detailed console logging
- Added date format validation and automatic format conversion
- Added fallback to default dates when existing dates can't be parsed
- Removed readme.txt in favor of unified GitHub-compatible readme.md
- Removed WordPress.org repository references as plugin is not hosted there

### 1.4.6
- Fixed date format issues in quick presets that caused incorrect dates
- Resolved spacing problems between start and end date/time fields
- Added consistent background styling for date and time range sections
- Standardized date format to ISO format (YYYY-MM-DD) for better compatibility
- Improved date validation to prevent incorrect date parsing
- Enhanced error handling and logging for date selection
- Optimized preset buttons styling and margins
- Added debug information to help troubleshoot date selection issues

### 1.4.5
- Fixed spacing issues between date and time range fields
- Improved layout of start and end input fields by positioning them closer together
- Enhanced responsive behavior for date and time controls on mobile devices
- Added compact row layouts for better field alignment
- Reduced unnecessary gaps in the UI for a more polished appearance
- Streamlined CSS for more consistent spacing throughout the interface

### 1.4.4
- Completely redesigned date range calendar to match plugin's style
- Enhanced calendar UI with WordPress admin color scheme integration
- Improved date picker usability and overall user experience
- Refined calendar input fields with better styling and visual cues
- Added improved hover and active states for date selections
- Updated calendar navigation for more intuitive month switching
- Improved date range display to better match time range controls
- Unified CSS styling between date and time pickers for consistency

### 1.4.3
- Fixed issue where tabs wouldn't appear immediately after enabling them
- Improved real-time tab visibility updates for better user experience
- Added auto page reload when necessary for newly added tabs
- Converted documentation to Markdown format for better GitHub compatibility
- Enhanced tab toggling logic for more immediate visual feedback

### 1.4.2
- Fixed critical issue with tab toggles not working in the Settings tab

### 1.4.1
- Fixed JavaScript error "setupToggleTabs is not a function" that was breaking tab functionality
- Improved initialization process for better stability
- Removed redundant function calls for improved performance

### 1.4
- Completely redesigned date range picker with modern UI
- Replaced the old date range picker with intuitive Flatpickr integration
- Added one-click date range presets (Today, Yesterday, Last 7 Days, etc.)
- Implemented visual date selection with modern calendar interface
- Unified the UI style between date and time pickers for consistency
- Improved date validation for better error prevention
- Enhanced visual feedback for date selection
- Added separate start and end date fields for easier range selection
- Improved mobile experience for date selection
- Enhanced preset buttons with active state indicators
- Fixed potential date format issues with international users
- Improved accessibility for date and time selectors
- Optimized date handling code for better performance

### 1.3.1
- Fixed critical issue with tab switching causing infinite refresh loops
- Removed problematic tab caching mechanism that was causing navigation issues
- Simplified tab navigation system for better reliability
- Improved JavaScript architecture for better stability
- Maintained all UI improvements from version 1.3 while fixing performance issues
- Enhanced tab switching to respect normal browser navigation

### 1.3
- Added modern time picker interface with flatpickr integration
- Added time presets (Business Hours, Morning, Afternoon, Evening)
- Implemented tab content caching for ultra-fast tab switching
- Replaced manual time inputs with elegant time pickers
- Added visual feedback during tab switching with loading indicators
- Improved CSS styling for better UI/UX
- Optimized JavaScript with modular structure
- Enhanced AJAX handling for better performance
- Added tab state persistence between page loads
- Updated minimum WordPress version to 6.7 for better performance
- Improved error handling and validation
- Reduced server load with more efficient tab rendering
- Fixed UI flickering when switching between tabs
- Added loading indicators during AJAX operations
- Implemented dynamic browser history updates for better navigation

### 1.2
- Added custom time range functionality for precise time control
- Users can now specify start and end times for date updates
- Time selections work in both 12-hour and 24-hour format based on WordPress settings
- Added validation to prevent invalid time entries
- Updates are now spread randomly within the specified time range for more natural distribution
- Improved time handling for both posts and comments
- Enhanced user interface for time selection controls
- Added additional validation for time inputs

### 1.1
- Added new Settings tab as the first tab for easy configuration
- Implemented AJAX-based tab management for toggling tab visibility without page reload
- Added 3 days and 7 days options to the date distribution dropdown
- Improved tab switching performance for smoother user experience
- Enhanced user interface with smoother transitions
- Updated resource section with WordPress for Photographers community
- Added Posts Remastered resource for improving SEO
- Added link to wplove.co blog for additional tutorials
- Added Plugin Support button for easier access to support
- Improved UI with better icon alignment and spacing
- Added activation hook to set default tab settings
- Updated all community links and removed outdated information
- Improved mobile responsiveness for better experience on smaller screens

### 1.0
- Complete plugin rebranding and code modernization
- Added dedicated admin menu for easier access
- Implemented batch processing for improved performance with large sites
- Enhanced security with proper data sanitization and validation
- Added memory management to handle sites with thousands of posts
- Improved UI with modern styling and better user feedback
- Fixed potential SQL injection vulnerabilities with prepared statements
- Added comprehensive validation for form inputs
- Improved error handling and user feedback
- Updated taxonomies handling for better compatibility
- Added null checks to prevent PHP warnings
- PHP 8.0-8.4 compatibility with type declarations
- WordPress 6.7.2 and 6.8 compatibility updates
- Fixed date range picker issues on some WordPress themes
- Added confirmation message with count of updated items
- Optimized database queries for better performance
- Implemented proper escaping for all output
- Updated UI with accessible color schemes
- Added responsive design improvements for mobile admin

## Requirements

- WordPress 6.7 or higher
- PHP 8.0 or higher

## Author

- [wplove.co](https://wplove.co)

## License

GPLv2 or later 