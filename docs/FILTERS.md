# Filters

The filters can be a really powerful option when parsing a file.  They can be used to validate file contents before ingesting them into the system, for manipulating the data by removing or adding characters, adding in other data so that it works better in a database, or creating an object from the row.

*NOTE: Filters do not work with Maps*

So given the file below

| ID      | Name                | Phone             |
| ------- | ------------------- | ----------------- |
| 1       | George Jetson       | +1 (234) 456-7890 |
| 2       | Fred Flintstone     | +1 1-1

Once you open the file you need to add the filter with a callback method.

```php
$reader = new Reader('/path/to/file.csv');
$filter = new Filter('Phone', [Employee::class, 'stripPhone']);
$reader->addFilter($filter);
```

Then you'll need to add the callback method to the appropriate class (making sure it is callable from the CSVReader class).

```php
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

If the data that you want to 'objectify' is all in one column you can use this to parse that data and turn it into an object. Example, address data. Your file has customer address data in one field (if it's in separate fields you'll need to use a [`Link`](./Links.md)). In this case, you are adding a filter to an existing column header, so when calling that field, it will 

| Address                           |
| --------------------------------- |
| 123 Main St, Anytown, ST 12345    |


First you need to create the `Address` object and the method you want to use to parse the data.

```php
class Address 
{
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
    ...
```

Then create the `Address` object within your `Customer` class.


You could then just have the reader pass the Address field directly into the address property in the Customer class.

```php
class Customer
{
    public Address $address;
    ...
}
```

Then you'll just need to open the file with the reader, add the filter (**before reading**), and then where you need it, read the file

```php
// opening the customer file
$reader = new Reader('/path/to/customer.csv');
// add the filter (must be done prior to reading)
$filter = new Filter('Address', [Address::class, 'fromCSV']);
$reader->addFilter($filter);
...
// create the customer and read the address
$customer = new Customer();
$customer->address = $reader->Address;
```
