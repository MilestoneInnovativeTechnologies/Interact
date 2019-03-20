The package which enables direct interaction with appframe project tables.

install appframe<br>
install interact<br>

Create class with same name of incoming table, which implements the interface _Milestone\Interact\Table_

Give common namespace for all such classes and mention that namespace in interact configuration file in config folder.

If any data from incoming interact file required, the define public properties in the class in the name _table_,_mode_,_primary_key_,_data_

If a method named, **isRecordValid**, is available, then this method will be called before executing each record.<br>
That particular record will be the only argument supplied to this method.<br>
This method should return boolean _true_, _false_ or _string_ mentioning the reason for invalid.

If a method named, **preActions**, is exists, then it will be called before starting the whole action.
It will be supplied with the argument, the total Content

If a method named, **postActions**, is exists, then it will be called after executing the whole action.
It will be supplied with the argument, the total Content and Result object.
This should respond with modified result which will be outputted or else with null to output default Result object