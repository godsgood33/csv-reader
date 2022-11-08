# Maps

The idea behind a map is that you can call an alias and it will read multiple fields from the file and return them as a string. After you've created your reader you need to add a map for any fields you want. Let's say I want to read an entire address at once, I would do so with the following:

## Customers.csv

| id | fname | lname | add | city | state | zip |
| -- | ----- | ----- | --- | ---- | ----- | --- |
| 1  | George | Jetson | 123 Main St | Anytown | ST | 12345 |

```php
$map = new Map('address', "%0\n%1, %2 %3", ['add', 'city', 'state', 'zip']);
$reader->addMap($map);
```

So instead of returning these independently, this would return a string formatted as follows:

```
123 Main St
Anytown, ST 12345
```

As you are reading through a file you can request `$reader->address` and it will read and output the formatted string as you've requested. Maps are available as long as that `$reader` object is available.

Another example would be wanting to concatenate first and last names together automatically for database insertion.

```php
$map = new Map('name', "%0 %1", ['fname', 'lname']);
$reader->addMap($map);

$name = $reader->name;  // returns 'George Jetson' from the above example.
```
