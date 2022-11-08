# Links

Links are made so you can return multiple fields together. When calling, it will return the fields as a `stdClass` object with each field name being the property. The best example of something like this would be a multi-column address.

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
    public static function fromCSV(stdClass $data): Address
    {
        $me = new static();

        // you'll need to do validation on each field

        $me->address = $data->Address;
        $me->city = $data->City;
        $me->state = $data->State;
        $me->zip = $data->Zip;
        $me->country = $data->Country;
        
        return $me;
    }
}
```

Then open your file and create the `Link`

```php
$reader = new Reader('Customers.csv');
$link = new Link(
    'objAddress',  // Field to call that will trigger this link
    [
        'Address', 'City', 'State', 'Zip', 'Country'
    ], // The fields as they appear in the CSV after removing invalid characters
    [
        Address::class, 'fromCSV'
    ], // The *optional* callable method to pass the data to
);
$reader->addLink($link);
```

Then you need to just call that aliased column for your method to trigger.

```php
$customer = new Customer();
$customer->address = $reader->objAddress;
```
