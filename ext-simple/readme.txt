ext-simple
 - an extended get-simple
 - extra simple
 - extensible
 
advantages:
 - actions and filters generalized - more usage scenarios
 - hierarchical navigation by default
 - themes have config.php for dependencies, etc.
 - no addslashes, htmlentities, no cdata - just storing and retrieving data from XML
 
 
 
Actions, Filters, etc.:
  Frontend:
    before-template
    before-header
    put-header() => 1, if done
    after-header
    before-navigation
    put-navigation($slug, $minlevel, $maxlevel, $type, $options) => 1, if done
    after-navigation
    before-content
    filter-content($content) => (un)modified content
    after-content
    before-footer
    put-footer() => 1, if done
    after-footer
    after-template
  Backend:
    
    
    