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
 - technical:
   - object oriented - classes designed for re-use in plugins
   - no addslashes, htmlentities, no cdata - just storing and retrieving data from XML
   - actions and filters generalized - more usage scenarios
   - includes lessPHP for use in themes, themes are easily configurable
   - setting locale/language and date/time formats is a base functionality
 
 
 
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
  
Settings:
  variants: each page might exist in multiple variants (e.g. languages)
    
    
    