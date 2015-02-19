# Entry URL Field

## Installation
 
1. Upload the 'entry_url_field' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Field: Entry URL", choose Enable from the with-selected menu, then click Apply.
3. The field will be available in the list when creating a Section.


## Usage

When adding this field to a section, the following options are available to you:

* **Anchor Label** is the text used for the hyperlink in the backend. An `<entry id="123">...</entry>` nodeset is provided from which you can grab field values, just as you would from a datasource. For example:

	Link to "{entry/name}"

* **Anchor URL** is the URL of your entry view page on the frontend. An `<entry id="123">...</entry>` nodeset is provided from which you can grab field values, just as you would from a datasource. For example:

	/members/profile/{entry/name/@handle}/

* **Open links in a new window** enforces the hyperlink to spawn a new tab/window
* **Hide this field on publish page** hides the hyperlink in the entry edit form

## XSLT & Datasources

Version 2.0 allows the use of XSLT templates to generate urls. Xpath statements can only be so complicated, the ability to use an XSLT template simplifies the use and generation of URLs especially when using other datasources to generate parts of the url. Such as a user friendly url containing the category, subcategory and article handles.

Datasources are selected within the Field Settings panel when adding/editing the field details. They are accessible via XPath and XSLT in the same way they are available in the front-end. Keep in mind the more you add the longer it might take to generate the urls. Note that if you change a value used to generate the urls, these will not automatically update, you will need to re-save the entries.

## Handle Field

As a bonus addition with version 2.0 there's also a custom handle field baked in to the Entry URL. Before when one wanted to customize the link handle, say taken from an Article Title, you would have to create a separate field, for the handle, and explain to the customer that that they have to write the link field there. The new update simplifies this process.

By Selecting a `handle_source` the javascript automatically ties itself to the original source field. When creating the entry, this will auto-generate a handle as the user fills in the orignal field. If they want to override the handle value, they can do so by clicking on the handle text. Once touched the handle will only sync when clicking on the refresh icon, same thing when opening an article which has been saved. Also once saved, the entry handle will appear as part of the full entry url, making it simpler for the person editing the entry to see the url with which the entry is accessible.

## Credits

Big thanks to Rowan Lewis' Reflection Field, which comprises about 90% of this code! Thx.
