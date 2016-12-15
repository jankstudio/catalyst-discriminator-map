# Catalyst Discriminator Map Bundle
Symfony bundle that allows the specification of Doctrine discriminator maps in bundle configuration files instead of under the parent entity.

This bundle removes the dependency of the parent entity on the children entities.

## Configuration

Minimum requirement is to have a dmap.yml file under the Resources/config directory of the bundle with the parent entity.

Sample dmap.yml file:

    entities:
        - parent: MyBundle\Entity\Person
          children:
            - id: person
              class: MyBundle\Entity\Person
            - id: employee
              class: MyBundle\Entity\Employee

You can have other dmap.yml files with the same parent entity in other bundles like so:

    entities:
        - parent: MyBundle\Entity\Person
          children:
            - id: customer
              class: AnotherBundle\Entity\Customer

## Discriminator Mapping Example

### How discriminator mapping is normally done:

Entity:

    <?php
    namespace MyProject\Entity;

    /**
     * @Entity
     * @InheritanceType("SINGLE_TABLE")
     * @DiscriminatorColumn(name="discr", type="string")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        // ...
    }

    /**
     * @Entity
     */
    class Employee extends Person
    {
        // ...
    }

### How to do it with this bundle:

Entity:

    <?php
    namespace MyProject\Entity;

    /**
     * @Entity
     * @InheritanceType("SINGLE_TABLE")
     * @DiscriminatorColumn(name="discr", type="string")
     */
    class Person
    {
        // ...
    }

    /**
     * @Entity
     */
    class Employee extends Person
    {
        // ...
    }

Configuration File (dmap.yml):

    entities:
        - parent: MyProject\Entity\Person
          children:
            - id: person
              class: MyProject\Entity\Person
            - id: employee
              class: MyProject\Entity\Employee
