@local @local_activitylibrary @core @javascript
Feature: As an admin I should be able to paginate activities in the activity library

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following config values are set as admin:
      | config                | value |
      | enableactivitylibrary | 1     |
    And the following "activities" exist:
      | activity | name    | intro | course | idnumber |
      | page     | Page 01 | I1    | C1     | PAGE01   |
      | page     | Page 02 | I2    | C1     | PAGE02   |
      | page     | Page 03 | I3    | C1     | PAGE03   |
      | page     | Page 04 | I4    | C1     | PAGE04   |
      | page     | Page 05 | I5    | C1     | PAGE05   |
      | page     | Page 06 | I6    | C1     | PAGE06   |
      | page     | Page 07 | I7    | C1     | PAGE07   |
      | page     | Page 08 | I8    | C1     | PAGE08   |
      | page     | Page 09 | I9    | C1     | PAGE09   |
      | page     | Page 10 | I10   | C1     | PAGE10   |
      | page     | Page 11 | I11   | C1     | PAGE11   |
      | page     | Page 12 | I12   | C1     | PAGE12   |
      | page     | Page 13 | I13   | C1     | PAGE13   |
      | page     | Page 14 | I14   | C1     | PAGE14   |
      | page     | Page 15 | I15   | C1     | PAGE15   |
      | page     | Page 16 | I16   | C1     | PAGE16   |
      | page     | Page 17 | I17   | C1     | PAGE17   |
      | page     | Page 18 | I18   | C1     | PAGE18   |

  Scenario: As an admin I can paginate through activities
    Given I am on site homepage
    And I log in as "admin"
    And I navigate to activity library "Home" page
    And I wait until the page is ready
    And I should see "Page 01"
    And I should see "Page 11"
    And I should not see "Page 13"
    And I click on "Next" "link"
    When I should see "Page 14"
    Then I should see "Page 18"

  Scenario: If I toggle page limit between reloads, it should persist
    Given I am on site homepage
    And I log in as "admin"
    And I navigate to activity library "Home" page
    And I wait until the page is ready
    And I click on "Show 12 items per page" "button"
    And I click on "25" "link"
    And I should see "Page 18"
    And I reload the page
    When I should see "Page 18"
    Then I should see "Show 25 items per page" "button"
