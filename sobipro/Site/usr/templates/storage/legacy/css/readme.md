The default7 template file for SobiPro 2 supports all Bootstrap versions to adapt to an old Joomla template.
In the default8 template, all Bootstrap code < Bootstrap 5 has been removed to simplify the code and allow for easier customisation.

It is highly recommended to make modifications which you had done on your template to the default8 template to get the full functionality this template has and have maximum
compatibility.

We offer a paid service to make your template default7+ based. Please contact us for a cost estimate.

------------------------------------------------------------------------------------------------------

The description below is for the adaption of SobiPro 2 applications to older SobiPro templates. This works only with the default7 template!!

If you want to use your old template, please bear in mind that not everything will work and look like before, although we added compatibility functions.
SobiPro cannot modify your template automatically, as each template is unique. A template is something what is under your control. Therefore, it is possible that you need to make
several modifications though.

In any case, you need to set 'Support for old templates' to 'Yes' (General configuration -> Template -> General Settings).

The next steps depend on the template your own template is based on.

Based on the default6 template:

1. Change the following in the hidden area at the end of your config.xml file:
    - Find <field name="settings[config][general][bs]" default="bs3"/> and change "bs3" to "3"
    - Find <field condition="apps.review_rating" name="settings[config][less][review][bsversion]" default="bs3"/> and change it
      to  <field condition="apps.review_rating" name="settings[config][less][review][bs]" default="3"/>
    - If the line <field name="settings[config][general][font]" default="fa4"/> exists, change "fa4" to "4".
      Otherwise, add <field name="settings[config][general][font]" default="4"/>
    - Add the line <field name="settings[config][general][development]" default="0"/>
      Save the template settings.

2. In General configuration -> Template -> General Settings select 'Bootstrap 3' for the Layout and 'Font Awesome 4' as Icon Font.

3. Copy the files storage/legacy/css/default6.less and storage/legacy/css/default6.css to the css folder of your template.
   The adaption in this file is made for the default Joomla template Cassiopeia. If you use another template, you may adapt some styles.

4. Copy the file(s) in storage/legacy/css/helper to a css/helper folder in your template.
   If not already exits, create the folder helper!
   In your theme.less file at the very end, add the line:
   .SobiPro { @import (less) "helper/_applications.linc"; }
   Save and compile your theme.less file.

5. In your template, change the files config.ini, search/search.ini and entry/edit.ini. IF there is a line starting with 'css_files', add 'default6', separated by comma after the
   last item.
   E.g. css_files = "theme,custom,default6"

6. Add to your css/theme.less file under "//Default values":
   @bs: 3;
   @white: #fff;
   @gray-100: #f8f9fa;
   @gray-200: #e9ecef;
   @gray-300: #dee2e6;
   @gray-400: #ced4da;
   @gray-500: #adb5bd;
   @gray-600: #6c757d;
   @gray-700: #495057;
   @gray-800: #343a40;
   @gray-900: #212529;

   Save and compile the file.

Note: The 'Description position' as set in the template configuration has precedence over the new field settings for the description position.

Based on the default5 template:

1. Change the following in the hidden area at the end of your config.xml file:
    - Find <field name="settings[config][general][bs]" default="bs3"/> and change "bs3" to "3"
    - Find <field condition="apps.review_rating" name="settings[config][less][review][bsversion]" default="bs3"/> and change it
      to  <field condition="apps.review_rating" name="settings[config][less][review][bs]" default="3"/>
    - If the line <field name="settings[config][general][font]" default="fa4"/> exists, change "fa4" to "4".
      Otherwise, add <field name="settings[config][general][font]" default="4"/>
    - Add the line <field name="settings[config][general][development]" default="0"/>
      Save the template settings.

2. In General configuration -> Template -> General Settings select 'Bootstrap 3' for the Layout and 'Font Awesome 4' as Icon Font.

3. Copy the files storage/legacy/css/default5.less and storage/legacy/css/default5.css to the css folder of your template.
   The adaption in this file is made for the default Joomla template Cassiopeia. If you use another template, you may adapt some styles.

4. Copy the file(s) in storage/legacy/css/helper to a css/helper folder in your template.
   If not already exits, create the folder helper!
   In your theme.less file at the very end, add the line:
   .SobiPro { @import (less) "helper/_applications.linc"; }
   Save and compile your theme.less file.

5. In your template, change the files config.ini, search/search.ini and entry/edit.ini. Find the line starting with 'css_files'. After the last item add 'default5', separated by
   comma.
   E.g. css_files = "theme,custom,default5"

6. Add to your css/theme.less file under "//Default values":
   @corner-radius: 10px;
   @bs: 3;
   @white: #fff;
   @gray-100: #f8f9fa;
   @gray-200: #e9ecef;
   @gray-300: #dee2e6;
   @gray-400: #ced4da;
   @gray-500: #adb5bd;
   @gray-600: #6c757d;
   @gray-700: #495057;
   @gray-800: #343a40;
   @gray-900: #212529;
   @black: #000;

   Perhaps add a different @corner-radius value, do not change the other settings.
   Save and compile the file.

Note: The 'Description position' as set in the template configuration has precedence over the new field settings for the description position.

Based on the default4 template:

1. Change the following in the hidden area at the end of your config.xml file:
    - Find <field name="settings[config][general][bs]" default="bs3"/> and change "bs3" to "3"
    - Find <field condition="apps.review_rating" name="settings[config][less][review][bsversion]" default="bs3"/> and change it
      to  <field condition="apps.review_rating" name="settings[config][less][review][bs]" default="3"/>
    - Add the line <field name="settings[config][general][font]" default="3"/>
    - Add the line <field name="settings[config][general][development]" default="0"/>
      Save the template settings.

2. In General configuration -> Template -> General Settings select 'Bootstrap 3' for the Layout and 'Font Awesome 3' as Icon Font.

3. Copy the files storage/legacy/css/default4.less and storage/legacy/css/default4.css to the css folder of your template.
   The adaption in this file is made for the default Joomla template Cassiopeia. If you use another template, you may adapt some styles.

4. In your template, change the files config.ini, search/search.ini and entry/edit.ini. If there is a line starting with 'css_files', add 'default4', separated by comma after the
   last item (e.g. css_files = "theme,custom,default4").

5. Copy the file(s) in storage/legacy/css/helper to a css/helper folder in your template.
   If not already exits, create the folder helper!
   In your theme.less file at the very end, add the line:
   .SobiPro { @import (less) "helper/_applications.linc"; }
   Save and compile your theme.less file.

6. Add to your css/theme.less file under "//Default values":
   @corner-radius: 10px;
   @bs: 3;
   @white: #fff;
   @gray-100: #f8f9fa;
   @gray-200: #e9ecef;
   @gray-300: #dee2e6;
   @gray-400: #ced4da;
   @gray-500: #adb5bd;
   @gray-600: #6c757d;
   @gray-700: #495057;
   @gray-800: #343a40;
   @gray-900: #212529;

   Perhaps add a different @corner-radius value, do not change the other settings.
   Save and compile the file.

Templates based on the default3 or default2 template are not supported at all.