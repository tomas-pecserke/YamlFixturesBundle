Getting Started With PecserkeYamlFixturesBundle
==============================================

Fixtures are used to load a controlled set of data into a database.
This data can be used for testing or could be the initial data required for the application to run smoothly.
Symfony2 has no built in way to manage fixtures but Doctrine2 has a library to help you write fixtures
for the Doctrine ORM or ODM.

## Prerequisites

This version of the bundle requires [Symfony 2.3+](http://symfony.com) and [Composer](http://getcomposer.org/).

## Installation

Add PecserkeYamlFixturesBundle in your composer.json:

``` js
{
    "require": {
        "pecserke/yaml-fixtures-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update pecserke/yaml-fixtures-bundle
```

Composer will install the bundle into your project's `vendor/pecserke` directory.

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Pecserke\YamlFixturesBundle\PecserkeYamlFixturesBundle(),
    );
}
```

##  Writing simple YaML Fixtures

Let's say we have an entity `Person` defined like this:

``` php
<?php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Person
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string")
     */
    private $lastName;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }
}
```

All you need to do is create a file in your bundle's `Resources/fixtures` directory formatted like this:

``` yaml
# AcmeDemoBundle/Resources/fixtures/person.yml
Acme\DemoBundle\Entity\Person:
    data:
        john_doe:
            firstName: John
            lastName: Doe
        jane_doe:
            firstName: Jane
            lastName: Doe
```

It's also possible to place your YaML fixtures into `app/Resources/YourBundleName/fixtures` directory.
In that case all files with same filename in in your bundle's `Resources/fixtures` directory
will be "overridden" by those from `app/Resources`. No merging is done.

Property `data` contains data, that will be transformed into database objects.
**Property of object must be public or accessible via setter method.**

## Loading fixtures

YaML fixtures are loaded in similar way to ordinary fixtures:

``` shell
$ php app/console pecserke:fixtures:load
```

## Using references

Reference is a named entity / document. Sometimes you need to use an object as a value of property of another.
Keys in `data` array are reference names which are assigned to each object.
In previous example it was `john_doe` and `jane_doe`.

Now let's say a `Person` has property `mate` and we want to specify Jane as John's mate
(as unidirectional association for now).

``` yaml
Acme\DemoBundle\Entity\Person:
    data:
        jane_doe:
            firstName: Jane
            lastName: Doe
        john_doe:
            firstName: John
            lastName: Doe
            mate: '@jane_doe'
```

Notice `jane_doe` is defined as first.
**At the time of loading object referencing another object,
we need the referenced one to be already loaded**.

## Ordering fixtures

As you can see, we often need a way to ensure order in which the fixtures are loaded.
The fixtures of same class in one file are loaded in same order, in which they are defined in YaML file.
We can control the order of fixture loading using `order` property.

So let's say, we want to define Jane in separate file (she still must be loaded before John),
and we added property `address` to `Person` and it also need to be loaded before the person.

**The `order` property is respected across all fixture files.**

``` yaml
# person_1.yml
Acme\DemoBundle\Entity\Address:
    order: 1
    data:
        address:
            street: 123 Main St
            city: Any town
            postalCode: CA 01234-5678

Acme\DemoBundle\Entity\Person:
    order: 2
    data:
        jane_doe:
            firstName: Jane
            lastName: Doe
            address: '@address'

# person_2.yml
Acme\DemoBundle\Entity\Person:
    order: 3
    data:
        john_doe:
            firstName: John
            lastName: Doe
            mate: '@jane_doe'
            address: '@address'
```

**Order in which fixtures with same value of `order` property and different class
(or specified in different files) is not specified.**

**Fixtures without specified order are loader after ordered fixtures.**

## Post-persist callback

It's possible to call function on different persisted object via reference after the current one has been persisted.

This is how we can solve our problem with both ways `mate` association between John and Jane.

``` yaml
Acme\DemoBundle\Entity\Person:
    data:
        jane_doe:
            firstName: Jane
            lastName: Doe
        john_doe:
            firstName: John
            lastName: Doe
            mate: '@jane_doe'
            '@postPersist': [ '@jane_doe', 'setMate', [ '@john_doe' ] ]
```

**Since the callback is called after the current entity / document has been persisted,
the reference to is already available.**

## Preventing duplicate entries

If you don't purge DB before loading fixtures (which you don't want to do in production environment),
you need a way, how to avoid duplicates. That's where yo use property `equal_condition`.
In it you specify the list of fields which combination define a unique entry.
If this list is present and not null or empty, only entries that are unique  are loaded.

``` yaml
Acme\DemoBundle\Entity\Person:
    equal_condition: [ firstName, lastName ]
    data:
        john_doe:
            firstName: John
            lastName: Doe
        jane_doe:
            firstName: Jane
            lastName: Doe
```

## Using DataTransformer

Property `@dataTransformer` allows you to transform an array into another value.

Dor example let `Person` have field `birthDay` of type `DateTime`.
As YaML only allows some types like arrays, strings and numbers, we need a way to transform them.

First we need to define `DateTimeDataTransformer`
implementing `Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface`:

``` php
<?php
namespace Acme\DemoBundle\DataTransformer;

use Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface

class DateTimeDataTransformer implements DataTransformerInterface
{
    public function transform($data)
    {
        return new \DateTime($data['date_time']);
    }
}
```

There are two ways how to use it. To define it as a DIC service,
or to provide a class name, in which case the class must provide default constructor.

``` yaml
Acme\DemoBundle\Entity\Person:
    equal_condition: [ firstName, lastName ]
    data:
        john_doe:
            firstName: John
            lastName: Doe
            birthDay: { '@dataTransformer': '@data_transformer.date_time', date_time: '1970-01-01 00:00:00' }
        jane_doe:
            firstName: Jane
            lastName: Doe
            birthDay:
                '@dataTransformer': 'Acme\DemoBundle\DataTransformer\DateTimeDataTransformer',
                date_time: '1970-01-01 00:00:00'
```

## Using custom ObjectTransformer

If the default transformation of data to database objects is not sufficient,
you can specify custom object transformer implementing
`Pecserke\YamlFixturesBundle\DataTransformer\DataTransformerInterface`.

``` yaml
Acme\DemoBundle\Entity\Person:
    transformer: '@service.name' # or class name - must provide default constructor
    data:
        john_doe:
            firstName: John
            lastName: Doe
        jane_doe:
            firstName: Jane
            lastName: Doe
```
