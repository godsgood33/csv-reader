# csv-reader

The purpose of this library is to simplify reading and parsing CSV files.  I have parsed CSV files so many times and there is not really an easy way to do it.  You have to know the index of the row you want to insert and it would be so much more readable if you could just use the header.  A while ago, I started reading the header row, flipping the array so that the index of the row elements is now the value of the field index.

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

```
composer require godsgood33/csv-reader
```

Then you need to pass your CSV filename into the class and create an object

```
$reader = new CSVReader($csvFilename);
```

If your file has different than the standard delimiter, enclosure, or the header row is not row 1 (0 based, so actually row 0), then you can alternatively pass in an array of options.

```
$reader = new CSVReader($csvFilename, ['delimiter' => ';', 'enclosure' => "'", 'header' => 2]);
```

The CSVReader will remove any non-alphanumeric characters `[^a-zA-Z0-9_]`.

After this is done, all you need to do is start looping until the end of the file is reached or the data you're looking for is found.

NOTE: CSVReader will automatically skip to the first row after the header after it is done parsing so DON'T use a `while` loop

```
do {
    ...
} while($reader->next());
```

Inside your loop you can use the header titles as field names to retrieve the data at each column

```
do {
    $column1 = $reader->column1;
    $column2 = $reader->column2;
} while($reader->next());
```

