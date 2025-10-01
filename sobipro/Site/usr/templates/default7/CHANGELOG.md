Changelog for Default Template V7 for SobiPro multi-directory component with content construction support
=

#### @package

SobiPro multi-directory component with content construction support

#### @author

Name: Sigrid Suski and Radek Suski, Sigsiu.NET GmbH  
Email: no-reply@sigsiu.net  
Url: https://www.Sigsiu.NET

#### @copyright

Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.  
@license GNU/GPL Version 3  
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.  
See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

#### Legend:

(*) Security Fix  
(#) Bug Fix  
(+) Addition  
(-) Removed  
(!) Change

### 7.2.5 (29 November 2024)

    (!) The Enter key can be used again to submit the search form (js/autosuggestjs)
    (!) The Tab key can now be used to select the auto suggested term (js/autosuggestjs)

    (#) Ajax url for the autoComplete script changed to SPLiveSite+'index.php' (js/autosuggestjs)

### 7.2.4 (24 September 2024)

    (#) Added 'alert-delta' to general Joomla message alert (common/messages.xsl)
    (#) Correction of schema.org for Review & Ratings Application (entry/details.xsl)

### 7.2.3 (22 May 2024)

    (#) Alpha index layout corrected (_bootstrap.linc)

### 7.2.2 (17 May 2024)

    (!) Utilities script extended

### 7.2.1 (02 May 2024)

    (!) Improvements of the default custom styles (custom.less)
    (!) Styles 'Elevated' and the normalized improved  (_elevated.linc, _normalize.linc)
    (!) General search input box only shows the field's placeholder (search.js)
    
    (#) Javascript error if the search box was removed from template (search.js)
    (#) Datepicker styles got overwritten (bootstrap.linc)
    (#) Carousel corrected and styles moved to _bs5.inc (theme.less, _bs5.linc, general.js). 
        Carousel supported only for Bootstrap 5.
    (#) Staples tab style improved and supported only for Bootstrap 5 (_mixins.linc).

### 7.2 (19 February 2024)

    (!) Colour theme 'shades of blue' adapted to Cassiopeia template
    (!) Show state of subcategories (category.xsl)
    (!) The BeforeStoreEntry() method got a third parameter with template configuration (template.php)
    (!) The BeforeSubmitEntry() method got a second parameter with template configuration (template.php)
    (!) Entry form validation messages improved
    (!) Handling of reCaptcha improved (template.php)
    (!) No edit or manage buttons if entry is expired (manage.xsl)

    (#) Post data were not retrieved in BeforeStoreEntry() (template.php)
    (#) Error messages of reCaptcha handled like field messages (template.php and edit.xsl)
    (#) reCaptcha did not work (template.php)
    (#) Top menu not visible in Joomla 3 and Bootstrap5

### 7.1.6 (17 November 2023)

    (+) Setting to move the category name below the icon
    
    (!) Category template settings won't be shown if set to 'none'
    (!) Distance above 'Show/Hide Categories' button set

    (#) Several changes in typography.xsl for markup validity
    (#) 'Show/Hide Categories' texts were not translated
    (#) Default template version was not of type string
    (#) Added some CSS code for Bootstrap 3

### 7.1.5 (15 September 2023)

    (!) Add line feed after each field's output
    
    (#) PHP 8 compatibility

### 7.1.4 (06 June 2023)

    (+) Support for price and term fields added

    (!) Usage of typed variables in template.php

    (#) Offset for description text not set correctly 
    (#) Active flag for edit entry item of topmenu not always set correctly

### 7.1.3 (28 April 2023)

    (!) Resize class changed from sp-title to spctrl-resize

### 7.1.2 (17 February 2023)

    (+) Possibility to switch off category name; name added as popover for images
    
    (!) Texts moved from translation.xml to template language file

    (#) Inherited CSS colours are inherited from default colours instead selected colour scheme

### 7.1.1 (31 December 2022)

    (!) Compatibility files moved to storage

### 7.1 (26 September 2022)

    (+) Template functions AddTitle() and SetTitle() added to template.php 
    (+) Support for collection view with continuous pagination
    (+) Support for collection button with continuous pagination for section/category view
    
    (!) Payment modal id removed
    (!) Navigation script revised to be jQuery free
    (!) Action parameter removed from configuration files as no longer used
    (!) Tagin styles improved
    
    (#) Problem fixed with pagination type 2 and continuous pagination
    (#) Style for noimage image corrected
    (#) Bootstrap 2 buttons, button groups and dropdown styles corrected
    (#) Manage button for Bootstrap 2 corrected
    (#) Styles added for Bootstrap 3 to remove Joomla 3 template influence
    (#) Styles added for Bootstrap 4 to remove Joomla 3 template influence
    (#) Discount nodes corrected (list.xsl)

### 7.0.3 (02 August 2022)

    (+) Styles for tagin and autoComplete scripts added

    (!) Categories per line can be set to flex (Bootstrap 4 and 5 only)
    (!) Applications listed under 'Applications' in the template settings are now in alphabetical order
    (!) Search suggest script changed from typeahead to autoComplete

    (#) SobiPro scope added to _utilities.linc for Bootstrap < 4
    (#) Quick search does not work; search button added
    (#) Bootstrap 2: still missing blank in class value in edit form
    (#) Collection button corrected
    (#) Bootstrap radius problem for btn-sm in button group
    (#) Navigation colour problem caused by normalize

### 7.0.2 (21 June 2022)

    (+) More Bootstrap 5 utilities added for lower Bootstrap versions

    (!) btn-mini style added to Bootstrap 2
    (!) Bootstrap 2 HTML output switched to Bootstrap responsive

    (#) Missing styles for Bootstrap 3 on Joomla 3
    (#) Label reference for content container wrong in search form
    (#) spctrl-search-form id doubled
    (#) Label reference for phrases container missing
    (#) Border colour of invalid focused elements wrong
    (#) Bootstrap 2: missing blank in class value in edit form
    (#) Bootstrap 2: offset and label position wrong in edit form for mobile devices
    (#) Undefined variable in default4 compatibility file
    (#) Bootstrap 3: Joomla template adds wrong styles to label class for mobile devices
    (#) Usage of wrong variable in sort.js

### 7.0.1 (20 May 2022)

    (!) Call to ratings stars in vcard moved
    (!) clearfix after content loop in details view removed
    (!) Bootstrap version now evaluated from core
    (!) Styles for Bootstrap 2 and 3 added/changed

    (#) Missing triggers for Bootstrap 2
    (#) Datepicker position for Bootstrap 2 and 3 corrected

### 7.0 (25 April 2022)

    (!) star class changed to sp-star
    
    (#) Textarea height for Bootstrap 2 and 3 wrong
    (#) input-group-text for Bootstrap 4 and 5 corrected

### 7.0 RC 4 (29 March 2022)

    (+) Template version added also to theme.less file
    
    (!) Distances for alpha menu changed
    (!) Some style changes

### 7.0 RC 3 (11 March 2022)

    (!) Alpha listing does no longer use grid system
    (!) Only tooltips and popovers with the attribute data-sp-toggle will be initialized automatically
    
    (#) Topmenu active state wrong if editing an entry
    (#) JS initialisation of tooltips and popovers wrong for Bootstrap < 5

### 7.0 RC 2 (24. February 2022)

    (!) Utility classes for Bootstrap < 5 separated in one helper file
    
    (#) Debug output removed
    (#) Modal window size wrong on mobile and Bootstrap 5
    (#) Some style improvements for Bootstrap 2 and 3

### 7.0 RC 1 (01 February 2022)

    (+) Round pagination example added to the typography template
    
    (!) Some corrections for Bootstrap 2 and 3 used with Joomla 4 default template
        NOTE! It is highly recommended to use the Bootsrap version your Joomla template provides for SobiPro!
    
    (-) Bootbox styles removed

    (#) Adaptions for Bootstrap 5 templates

### 7.0 Alpha 2 (19 October 2021)

    (#) Missing semicolon in some javascript files

### 7.0 Alpha 1 (01 October 2021)

    (+) Setting 'Category icon width' added
    (+) Possibility to set image width to 100% in template settings
    (+) Framework (Bootstrap) and font of template will be set in general configuration
    (+) Font sizes 17px and 19px added
    (+) Evacuated LESS code in helper modules
    (+) New colour themes for the colours red, green, blue and grey
    (+) Styles for applications will be installed with the application itself
    (+) Container width for the forms (when to break on mobiles)
    (+) Supports Bootstrap 3, Bootstrap 4 and Bootstrap 5 layout
    (+) Possibility to choose a 'flex' number of entries per line and to set a minimum width for them
    (+) Accessibility
    (+) Entry form buttons (save, cancel) can be set sticky
    (+) More navigation styles added
    (+) Compatibility styles added for old templates
    
    (-) Font sizes 11px and 12px removed
    
    (!) Color picker updated. Incompatible with older templates!
    (!) Adaption to SobiPro 2.x layout
    (!) Usage of Font Awesome5 Free possible
    (!) Usage of Sobi Framework functions
    (!) Suffixes for radio and check boxes are now appended to the option label by the system
    (!) Suffixes in forms are handled by the system now
    (!) Description position setting moved to fields manager
    (!) Usage of new load icon function SobiPro::Icon to load the icon according to a select font
    (!) Suffixes for field types #multiselect' and 'chbxgroup' are added after each option
    (!) Buttonbar: add and search button are on right side by default
    (!) All size values are in rem now; Base size = 16px
    (!) Included less files starts with underscore, gets extension '.linc' and cannot be compiled directly
    (!) themes folder moved below CSS folder
    (!) SobiPro specific typography styles (for button, background and text) are renamed to alpha, beta, gamma and delta
    (!) Frontend tree styles are now included in theme.less.
    (!) Redesign of all colour themes with respect to accessibility
    (!) Labelling the colour themes with the colours they use
    (!) Improving the elevated style
    (!) Template settings of applications loaded separately from folder 'settings'
    (!) Searchfield template does no longer need the position
    (!) Separate template settings file for search: search.json
    (!) Typeahead script now loaded from bootstrap.utilities.typeahead.js
    (!) Autosuggest to work with Bootstrap 4 and 5
  	(!) Setting for basic template development support (fields highlighting) moved to template settings
  	(!) Normalizing CSS moved to template and set to on by default
    
    (#) Sub-categories separator within text element to preserve blank
    (#) payment/list.xsl used deprecated attributes
    (#) Set the selected alpha switch item to active

### 6.2 (30 November 2020)

    (!) Calendar view styles
    (!) Distances in entry form

    (#) Added category id for entries ordering from drop-down box

### 6.1 (30 September 2020)

    (!) Alert colours changed
    
    (#) Add entry to category button shown only if user has the rights to
    (#) 'SPGeoMapsReg' is undefined
    (#) Status 'expired' is not shown

### 6.0 (31 July 2020)

    (+) Image placeholders improved (css or image; no, yes, controlled; float)
    (+) Extra layout added for alpha index
    (+) Tabular layout for details view and vcard
    (+) Support for Contactform Field
    (+) Google reCaptcha added to entry form
    
    (-) Correction CSS files and folder tmpl removed as SobiPro normalizes the styles now; still necessary corrections should be made in the custom.less/css file
    
    (!) New styles for theme 'sobipro'; former 'sobipro' theme renamed to 'power'
    (!) Corner values instead of rounded/angular
    (!) Manage and Search button get important colour
    (!) Border colour of sigsiu buttons set to background colour
    (!) Headers (h1,h2) normalised
    
    (#) Custom link colour missing in settings
    (#) Url field layout in entry form corrected
    (#) Some CSS tweaks

### 5.2 (29 May 2020)

    (!) Changelog included in SobiPro's changelog
