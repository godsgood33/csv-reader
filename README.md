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
$reader = new CSVReader($csvFilename);
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
$reader = new CSVReader($csvFilename, ['delimiter' => ';', 'enclosure' => "'", 'header' => 1, 'propToLower' => true]);
```

The CSVReader will remove any non-alphanumeric characters `[^a-zA-Z0-9_]`.

After this is done, all you need to do is start looping until the end of the file is reached or the data you're looking for is found.

NOTE: CSVReader will automatically read the first row after the header after it is done parsing so **DON'T use a `while` loop**

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
$reader = new CSVReader('file.csv', ['required_headers' => [
    'name', 'phone', 'email'
]]);
```

This will throw a `MissingRequiredHeader` exception if the required headers are missing.  The required headers need to be formatted just like the headers will be once all invalid characters are removed or converted to lowercase.

## Aliases

If you would like to specify that a field has an alias name you can specify that with the `'alias'` associative array option.  The key parameter is the alias and the value is the field it points to.

```
$reader = new CSVReader('file.csv', ['alias' => ['digits' => 'phone']]);
```

The above would allow you to use the either of the following:

```
$phone = $reader->digits;
$phone = $reader->phone;
```

## Maps

<!-- [Full Explanation](./MAPS.md) -->

Maps in CSVReader are a way that you can read multiple fields at once and return all of them in a formatted string.  After you've created your reader you need to add a map for any fields you want.  Let's say I want to read an entire formatted address at once, I would do so with the following:

```
$reader->addMap('address', "%0\n%1, %2 %3", ['add', 'city', 'state', 'zip']);
```

As you are reading through a file you can request `$reader->address` and it will read and output the formatted string as you've requested.  Maps are available as long as that `$reader` object is available.

Another example would be wanting to concatenate first and last names together automatically for database insertion.

```
$reader->addMap('name', "%0 %1", ['fname', 'lname']);
```

## Filters

[Full Explanation](./FILTERS.md)

Filters can be a really powerful option when parsing a file.  They could be used to validate the file contents before ingesting them into the system, for manipulating the data, removing or adding characters, adding in other data so that it works better in a database or HTML, or creating an object from a dataset.  Filters can be used along with aliases, but you **MUST** do the filter on the field that is in the file...**not** the alias.  Filters do not work with Maps as those already include their own callback so you can do that functionality if you want.

So in the alias example above, you must assign the filter on `phone` not `digits`.
