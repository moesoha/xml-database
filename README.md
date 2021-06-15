# XML ODM @ PHP

This is an ODM (Object Document Mapper) for XML. It uses XML to store data and uses XSD (XML Schema Definition) to validate values and structure. It supports attribute, reflection, and many other PHP features. It can save XML document in file or in Redis, or anything you like (once you implement `StoreInterface` for it).

It is NOT recommended using this library in a formal project, because this is just one of my toys and also the final project of a course called *XML Program Design*.

For how to use this library, `SohaJin/Toys/XmlDatabase`, you can just check `example/` folder which contains almost every feature this library could provide. 
