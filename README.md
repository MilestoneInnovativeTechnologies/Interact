The package which enables direct interaction with appframe project tables.

install appframe<br>
install interact<br>

Create class with same name of incoming table, which implements the interface _Milestone\Interact\Table_

Give common namespace for all such classes and mention that namespace in interact configuration file in config folder.

If any data from incoming interact file required, the define public properties in the class in the name _table_,_mode_,_primary_key_,_data_