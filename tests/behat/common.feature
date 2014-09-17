@mod @mod_dataform @dataformview @dataformview_pdf
Feature: Common

    @javascript
    Scenario: Manage view
        Given I run dataform scenario "manage view" with:
            | viewtype | pdf |


    @javascript
    Scenario: Required field
        Given I run dataform scenario "view required field" with:
            | viewtype      | pdf          |
            | entrytemplate | Entry template|

            
    @javascript
    Scenario: Submission buttons
        Given I run dataform scenario "view submission buttons" with:
            | viewtype  | pdf       |
            | actor     | student1  |
