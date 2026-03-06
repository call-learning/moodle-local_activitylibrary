@local @local_activitylibrary @core @javascript
Feature: As an admin I should be able to filter activities using module custom fields

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following config values are set as admin:
      | config                | value |
      | enableactivitylibrary | 1     |
    And the following "local_activitylibrary > category" exist:
      | component             | area         | name                             |
      | local_activitylibrary | coursemodule | Activity Library: Generic fields |
    And the following "local_activitylibrary > field" exist:
      | component             | area         | name            | customfieldcategory              | shortname | type |
      | local_activitylibrary | coursemodule | Test Field Text | Activity Library: Generic fields | CF1       | text |
    And the following "activities" exist:
      | activity | name      | intro     | course | idnumber |
      | page     | PageName1 | PageDesc1 | C1     | PAGE1    |
      | page     | PageName2 | PageDesc2 | C1     | PAGE2    |
    And the following "local_activitylibrary > fielddata" exist:
      | fieldshortname | value  | courseshortname | activityidnumber |
      | CF1            | ABCDEF | C1              | PAGE1            |
      | CF1            | ZZZZZZ | C1              | PAGE2            |

  Scenario: As an admin I can filter activities by a module custom field value
    Given I am on site homepage
    And I log in as "admin"
    And I navigate to activity library "Home" page
    And I expand all fieldsets
    And I set the field "Test Field Text" to "ABCDEF"
    When I click on "filterbutton" "button"
    Then I wait until the page is ready
    And I should see "PageName1"
    And I should not see "PageName2"

  Scenario: As an admin I can hide then show a custom field filter
    Given I am on site homepage
    And I log in as "admin"
    And I navigate to activity library custom field management page
    And I hide fields filter "CF1"
    And I wait until the page is ready
    And I navigate to activity library "Home" page
    And I expand all fieldsets
    And I should not see "Test Field Text"
    And I navigate to activity library custom field management page
    And I show fields filter "CF1"
    And I wait until the page is ready
    And I navigate to activity library "Home" page
    And I expand all fieldsets
    Then I should see "Test Field Text"
