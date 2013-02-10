ExtSimple
 - an extended GetSimple
 - extra simple
 - extensible
 
advantages compared to GetSimple:
 - functional:
   - hierarchical navigation by default
   - themes are easily configurable
   - supports multi-language sites out-of-the-box
   - support non-western (e.g. russian) URLs
   - easily support
 - technical:
   - object oriented - classes designed for re-use in plugins
   - no addslashes, htmlentities, no cdata - just storing and retrieving data from XML
   - actions and filters generalized - more usage scenarios
   - includes lessPHP for use in themes, themes are easily configurable
   - setting locale/language and date/time formats is a base functionality
 
 
 
Actions, Filters, etc.:
  Frontend:
    veto-page($page) => read, if page is forbidden
    init-page
    before-page
    Template:
      before-header
      put-header() => true, if done
      after-header
      before-navigation
      put-navigation($slug, $minlevel, $maxlevel, $type, $options) => true, if done
      after-navigation
      before-content
      filter-content($content) => (un)modified content
      after-content
      before-footer
      put-footer() => true, if done
      after-footer
      after-template
  Backend:
    veto-admin-page
    init-admin-page
    (process-plugin-xxx)
    before-admin-page
    Template
      before-admin-header
      after-admin-header
      before-admin-tabs
      add-admin-tab
      after-admin-tabs
      before-admin-actions
      add-admin-action-xxx
      after-admin-actions
      (display-plugin-xxx)
      before-admin-footer
      after-admin-footer
    Pages:
      create-page
      get-page-slug
      edit-page-options($page)
      edit-page-content($page)
      edit-page-extras($page)
      get-page-slug($page)
      before-save-page($page)
      after-save-page($page)
      undo-save-page($slug)
      after-delete-page($slug)
      undo-delete-page($slug)
      after-rename-page($slug, $newSlug)
      undo-rename-page($oldSlug, $slug)
    Components:
      edit-component-extras($component)
      get-component-slug($component)
      before-save-component($component)
      after-save-component($component)
      undo-save-component($slug)
      after-delete-component($slug)
      undo-delete-component($slug)
      after-rename-component($slug)
      undo-rename-component($slug)
    Files:
      after-upload-file
      edit-file-extras
      before-save-file
      after-save-file
      undo-save-file
      after-delete-file
      undo-delete-file
    Theme:
      after-save-theme
      undo-save-theme
    Plugins:
      process-plugin-xxx (in load.php, before before-admin-page)
      display-plugin-xxx (in load.php, after
      edit-plugin-extras
    Settings:
      edit-settings-extras
      before-save-settings
      after-save-settings
      undo-save-settings
    Users:
      edit-user-extras
      before-save-user
      after-save-user
      undo-save-user
      after-delete-user
      undo-delete-user
  
Settings:
  variants: each page might exist in multiple variants (e.g. languages)
    
    
    