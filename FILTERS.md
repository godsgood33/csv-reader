# Filters

The filters can be a really powerful option when parsing a file.  The could be used to validate the file contents before ingesting them into the system, for manipulating the data and removing or adding characters, adding in other data so that it works better in a database, or creating an object from a dataset.

*NOTE: Filters do not work with Maps*

So given the file below

```
ID      | Name                | Phone
1       | George Jetson       | +1 (234) 456-7890
2       | Fred Flintstone     | +1 1-1
...
```

Once you open the file you need to add the filter with a callback method.

```
$reader = new CSVReader('/path/to/file.csv');
$reader->addFilter('Phone', [Employee::class, 'stripPhone']);
```

Then you'll need to add the callback method to the appropriate class (making sure it is callable from the CSVReader class).

```
public static function stripPhone($val): string
{
    return preg_replace("/[^0-9\+]/", "", $val);
}
```

If you follow the above it will return

```
+1234567890
+111
```

Another example, address data.  So you have a file that has customer data and each customer's address data is all in one field instead of separate fields.  You want to split those into a `Address` class object.

```
...
... | Address                           | ...
... | 123 Main St, Anytown, ST 12345    | ...
...
```

So you need to then create a method that will parse that data and return the appropriate object

```
$reader = new CSVReader('/path/to/customer.csv');
$reader->addFilter('Address', [Address::class, 'fromCSV']);
```

This will then call the `fromCSV` method.

```
class Address 
{
    ...
public static function fromCSV(string $data): Address
{
    $me = new static();
    $add_arr = explode(',', $data);
    $me->add = $add_arr[0];
    $me->city = trim($add_arr[1]);
    $state_zip = explode(' ', $add_arr[2]);
    $me->state = trim($state_zip[0]);
    $me->zip = trim($state_zip[1]);

    return $me;
}
```

You could then just have the reader pass the Address field directly into the address property in the Customer class.

```
class Customer
{
    private Address $address;
    ...
}

$customer = new Customer();
$customer->address = $reader->Address;
```
