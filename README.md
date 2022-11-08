# CSV Reader

## Intro

The purpose of this library is to simplify reading and parsing CSV files.  I have parsed CSV files so many times and there is not really an easy way to do it.  You have to know the index of the row you want to insert and it would be so much more readable if you could just use the header title itself as the field index.  A while ago, I started reading the header row, flipping the array so that the index of the row elements is now the value of the field index.

```
$header = array_flip(fgetcsv($fh));
/*
 array(
     'column1' => 0,
     'column2' => 1,
     'column3' => 2,
 )
 */
```

This allows you the ability to use the header title as an index into the data array.

```
$column1 = $data[$header['column1']];
```

This is nice, but isn't any more readable than just using the index itself (or store the index in a variable and using that)...

```
$column1 = $data[$column1HeaderIndex];
```

So what I thought I would do is create a library that would allow you to use the header titles as field names.  Here's how you do it.

## Installation/Setup

```
composer require godsgood33/csv-reader
```

## Use

Pass your CSV filename into the class and create an object

```
$reader = new Reader($csvFilename);
```

### **Options**

These options are available as an associative array as the second parameter of the constructor

- delimiter - **,** (default)
  - what character separates the fields
- enclosure - **"** (default)
  - what character surrounds a field should it contain the delimiter character
- escape - **\\** (default)
  - what character will escape an enclosure character
- header - **0** (default)
  - what row (0-based) is the header title row on
- propToLower - **false** (default)
  - convert header titles to lowercase
- alias - **[]**
  - an array of aliases and what field they point to
    - [
        'name' => 'Name',
        'phone' => 'PhoneNumber',
        'email' => 'Email'
    ]
- required_headers - **[]**
  - what headers are required to be present in the file *(formatted the way they would be after invalid character removal or converting to lower case)*
    - ['Name', 'PhoneNumber', 'Email']

```
$reader = new Reader($csvFilename, ['delimiter' => ';', 'enclosure' => "'", 'header' => 1, 'propToLower' => true]);
```

The Reader will remove any non-alphanumeric characters `[^a-zA-Z0-9_]`.

After this is done, all you need to do is start looping until the end of the file is reached or the data you're looking for is found.

NOTE: Reader will automatically read the first row after the header after it is done parsing so **DON'T use a `while` loop**

```
do {
    ...
} while($reader->next());
```

Inside your loop you can use the header titles as field names to retrieve the data at each column

```
do {
    $name = $reader->name;
    $phone = $reader->phone;
    $email = $reader->email;
} while($reader->next());
```

## Required Headers

If there are required headers that you must have in your file you can specify them in the 'required_headers' option passed in at instantiation time.

```
$reader = new Reader('file.csv', ['required_headers' => [
    'name', 'phone', 'email'
]]);
```

This will throw a `MissingRequiredHeader` exception if the required headers are missing.  The required headers need to be formatted just like the headers will be once all invalid characters are removed or converted to lowercase.

## Aliases

If you would like to specify that a field has an alias name you can specify that with the `'alias'` associative array option.  The key parameter is the alias and the value is the field it points to.

```
$reader = new Reader('file.csv', ['alias' => ['digits' => 'phone']]);
```

The above would allow you to use the either of the following:

```
$phone = $reader->digits;
$phone = $reader->phone;
```

## Maps

[Full Explanation](./docs/MAPS.md)

Maps in `CSVReader` are a way that you can read multiple fields at once and return all of them in a formatted string.

## Filters

[Full Explanation](./docs/FILTERS.md)

Filters can be a really powerful option when parsing a file. They can be used to validate the field values before ingesting them into the system, for manipulating the data, removing or adding characters, adding in other data so that it works better in a database or HTML, or creating an object from a field value.  Filters can be used along with aliases, but you **MUST** do the filter on the field that is in the file...**not** the alias. In the example above, the filter would have to be assigned to the `phone` field and not `digits`.  Filters do not work with Maps as those already include their own callback so the filter functionality can be accomplished in the `Map` callback.

## Links

[Full Explanation](./docs/LINKS.md)

Links are a way that you can retrieve multiple fields at the same time and return them as a `stdClass` object or create your own object.