The package which enables direct interaction with appframe project tables.

install appframe<br>
install interact<br>

Create class with same name of incoming table, which implements the interface _Milestone\Interact\Table_

Give common namespace for all such classes and mention that namespace in interact configuration file in config folder.

<hr>

If any data from import interact file required, the define public properties in the class in the name _table_,_mode_,_primary_key_,_data_ 

If a method named, **isValidImportRecord**, is available, then this method will be called before executing each record.<br>
That particular record will be the only argument supplied to this method.<br>
This method should return boolean _true_, _false_ or _string_ mentioning the reason for invalid.

If a method named, **recordImported** exists, then this method will be called after executing each record.<br>
This method received that particular record and id of execution.<br>
Return value is ignored.

If a method named, **preImport**, is exists, then it will be called before starting the import action.
It will be supplied with the argument, the total Content.
Return value is ignored.

If a method named, **postImport**, is exists, then it will be called after executing the whole action.
It will be supplied with the argument, the total Content and Result object.
This should respond with modified result which will be outputted or else with null to output default Result object

<hr>
If any data for export action is required like _table_,_created_,_updated_ then define public properties in the class in same name

If a method named, **isValidExportGet** OR **isValidExportUpdate**, is available, then this method will be called before executing each record of insert mode or update mode respectively.<br>
That particular record will be the only argument supplied to this method.<br>
This method should return boolean _true_ or _false_.

If a method named, **recordGetExported**, **recordUpdateExported** exists, then this method will be called after executing each record of insert mode or update mode respectively.<br>
This method received that particular record and id of execution.<br>
Return value is ignored.

If a method named, **preExportGet**,**preExportUpdate**, is exists, then it will be called before starting the export action of mode insert and update respectively.
It will be supplied with the argument, the query to be executed.
This method should return the same or modified query or null;

If a method named, **postExportGet**,**postExportUpdate**, is exists, then it will be called after executing the whole action.
It will be supplied with the argument, the eloquent record and exported record.
Return value is ignored.

<hr>
<h2>USAGE</h2>
For importing to web, send a file with json content having multiple activity, each activity composed of table,primary_key,mode and data. where data will have all records.
url: /interact

For exporting from web, request to url in the format
/interact/{tablename}?created_at=datetime&updated_at=datetime&format=xml&type=file

The root parameter interact can be changed to desired one in config