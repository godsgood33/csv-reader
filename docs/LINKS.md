# Links

Links are made so you can link multiple fields together it will return them as an associative array with the column headers being the key to each value. The best example of something like this would be a multi-column address, or if you wanted to read a row and pass some elements to a static factory method to create an object.

## Customer.csv

| ID | Name   | Address     | City    | State | Zip   | Country |
| -- | ------ | ----------- | ------- | ----- | ----- | ------- |
| 1  | George | 123 Main St | Anytown | ST    | 12345 | US      |
| 2  | Frank  | 321 2nd St  | Mytown  | ST    | 54321 | US      |

So first create your Customer object

```php
class Customer
{
    public Address $address;
}
```

Then create your `Address` object.

```php
class Address
{
    public static function fromCSV(array $data): Address
    {
        $me = new static();
        $me->address = $data['Address'];
        $me->city = $data['City'];
        $me->state = $data['State'];
        $me->zip = $data['Zip'];
        $me->country = $data['Country'];
        return $me;
    }
}
```

Then open your file and create the `Link`

```php
$reader = new Reader('Customer.csv');
$link = new Link(
    'objAddress',  // Field to call that will trigger this link
    [
        Address::class, 'fromCSV'
    ], // The callable method to pass the data to
    [
        'Address', 'City', 'State', 'Zip', 'Country'
    ] // The fields as they appear in the CSV after removing invalid characters
);
$reader->addLink($link);
```

Then you need to just call that aliased column for your method to trigger.

```php
$customer = new Customer();
$customer->address = $reader->objAddress;
```

## Sales-Data.csv

A second example would be sales data, that you want to combine into one object
