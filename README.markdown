# Section Schemas

## Installation
1. Upload the 'section_schemas' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting "Section Schemas" from the System > Extensions menu, choose Enable from the with-selected menu, then click Apply.
3. Navigate to Blueprints > Data Sources, click Create New and choose Section Schema from the "Source" dropdown

## Usage
1. Create a schema data source by choosing the section and the fields to include
2. Attach the data source to a page

## I get weird errors!
Some fields simply are not compatible with this extension, because I use some slightly nefarious procedures to get the data I need. I'm abusing methods in Symphony's core which weren't really meant for this. It works for most fields, but some throw a hissy-fit, so I keep an array of these and ignore them from the schema output:

    $_incompatible_publishpanel = array('mediathek', 'subsectionmanager', 'imagecropper', 'readonlyinput', 'author', 'entry_versions', 'status');

If you get errors from other fields, let me know and I'll add them to this array. Alternatively, don't select them when creating the data source.

But if your complex field is throwing errors and you still want it to play nicely with the extension, go ahead and implement an `appendFieldSchema` method in your field which returns an `XMLElement` representing the schema of your field. Read more in [issue #6](https://github.com/nickdunn/section_schemas/pull/6).