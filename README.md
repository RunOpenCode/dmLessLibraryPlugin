dmLessLibraryPlugin for Diem Extended
===============================

Author: [TheCelavi](http://www.runopencode.com/about/thecelavi)
Version: 0.5
Stability: Stable  
Date: June 24th, 2013
Courtesy of [Run Open Code](http://www.runopencode.com)   
License: [Free for all](http://www.runopencode.com/terms-and-conditions/free-for-all)

dmLessLibraryPlugin for Diem Extended is LESS compiler for Diem Extended projects. For now, it can be used
via task from console, while integration with response object is work in progress.

The following commands are available:

- `php symfony less:clear-cache` or `php symfony less:cc` - Clears the compilation cache, if any is used. Current
LESS compiler [http://leafo.net/lessphp](http://leafo.net/lessphp) uses file cache. Other future implementation may not
use this feature.
- `php symfony less:compile` or `php symfony lessc` - Compiles the LESS files to CSS files for project. If, per example,
there is file called `file.less` it will be compiled and stored at same directory under the name `file.css`.
- `php symfony less:delete-css` or `php symfony less:delcss` - Deletes compiled CSS files from LESS files. It does that
according the file names (if in directory exists `file.less` and `file.css` - the conclusion is that CSS file is compiled
LESS file, so it will be removed).
- `php symfony less:delete-less` - Deletes LESS files from the project. Ought to be used ONLY in production server. Backup
your project before using this command.

There are various settings for each task. They are explained here:

Settings for tasks:
---------------------

###`less:clear-cache`:

- No additional settings

###`less:compile`:

- `plugin`: You can set which plugin will be searched for LESS files. Default is null, so whole project is searched for LESS files.
Several predefined constant exists:
    - web: it will search `web/theme` and `web/themeAdmin` dir for less files
    - core: searches in `diem-extended/dmCorePlugin/web`
    - admin: searches in `diem-extended/dmAdminPlugin/web`
    - front: searches in `diem-extended/dmFrontPlugin/web`
    - diem: searches in `diem-extended/dmAdminPlugin/web`, `diem-extended/dmCorePlugin/web`, `diem-extended/dmFrontPlugin/web`
    - plugins: searches in `plugins` dir
    - anyNameOfPlugin: searches in `project/plugins/anyNameOfPlugin`
    - NOTE: You can provide several search locations separating them with coma, example: `php symfony less:compile --plugin=web,front`
- `enabled-plugins-only`: When searching in plugins dir for plugins, should only enabled plugins in configuration be considered, default is false
- `force`: It will force compiler to compile LESS files regardless of cache, default is false
- `write-empty`: If file is empty, or output is empty, it will be written anyway. Default is false.
- `preserve-comments`: Should LESS comments be preserved or not in compiled CSS file, default is false
 
### `less:delete-css`

- `plugin`: Same as for `less:compile`
- `enabled-plugins-only`: Same as for `less:compile`

### `less:delete-less`

- `plugin`: Same as for `less:compile`
- `enabled-plugins-only`: Same as for `less:compile`


