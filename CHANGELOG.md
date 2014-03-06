CHANGELOG for 1.x
===================

This changelog references the relevant changes (bug and security fixes) done
in 1.x versions.

To get the diff for a specific change, go to https://github.com/symfony/symfony/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/symfony/symfony/compare/v2.4.0...v2.4.1

* 1.4.0 (2014-03-06)
    - fix many bugs
    - fix some tests
    - feature to get model's MongoCollection instance (72b4e851a7)
    - feature add MongoAggregate (f1aab0e619)
    - feature set model to not use __type (ad9635bc3f)
    - feature map fields for retrieve fields and sort (34fa582a91)
    - feature Model::has() query to if collection has (b7c9aa43b7)
    - enhance change some methods from private to protected
    - enhance Model::toArray() to support recursive and depth for DBRefs (f915eab57d)
    - chore add Travis CI PHP 5.5 test
    - chore refactored code to keep DRY
    - chore add lots of tests (da5c88ed27)

Much thanks to @andreychuk, @timothy-r, @purekid and @jrschumacher

* 1.3.0 (2013-12-17)
    - style #31 Move code style to [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)