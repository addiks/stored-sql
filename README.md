# Stored SQL

[![Build Status](https://travis-ci.com/addiks/stored-sql.svg?branch=master)](https://travis-ci.com/addiks/stored-sql)
[![Build Status](https://scrutinizer-ci.com/g/addiks/stored-sql/badges/build.png?b=master)](https://scrutinizer-ci.com/g/addiks/stored-sql/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/addiks/stored-sql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/addiks/stored-sql/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/addiks/stored-sql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/addiks/stored-sql/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/addiks/stored-sql/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

## WARNING: Unfinished, Work in progress! ##

This library provides a very flexible and dynamic toolset to analyse and manipulate SQL statements and SQL-segments.
In contrast with other SQL libraries, this can deal with snippets of SQL. A snippet like `WHERE foo.bar = "Lorem"`
is a perfectly fine and valid piece of SQL. It may not be runnable on a server itself (and this library knows this),
but it can be worked with even if the rest of the query is unknown and / or missing.

The main purpose of this is to be able to store SQL parts in the database and use them as very flexible, user-definable
rules that can easily be validated and merged into a real SQL query that then runs on the server.

For example, if you have a warehousing system and want to determine which warehouse should fulfill a delivery, and
have this rule user-definable and changable at any time, you could store the rules in the database like this:

|  ID | warehouse_name     | delivery_rule                                                             | priority |
|-----|--------------------|---------------------------------------------------------------------------|----------|
| jkl | QA (Handles 3%)    | WHERE RANDOM() % 30 = 1                                                   |     1000 |
| ghi | Nightshift         | WHERE TIME() NOT BETWEEN "06:00" AND "20:00"                              |      400 |
| def | Warehouse for USA  | LEFT JOIN deliveries d ON(w.id = d.warehouse) WHERE d.countryCode = "USA" |      200 |
| abc | Fallback Warehouse | WHERE 1                                                                   |      100 |

As you can see, we have stored very complex rules in the database without the need for any additional tables and in a
way that can make use of the complete SQL capabilities.

## The security question

So, to answer the obvious next question: Why is this not a huge security risk?
What if someone enters a "rule" like `DROP TABLE orders`? Would that not delete the whole orders table?

The answer to that is: No, it would simply produce an error.

Every SQL snippet is tokenized and parsed into an AST tree, you only need to look at the root nodes to understand what
the query is. A snippet like `LEFT JOIN foo f ON(f.a=b.id) WHERE f.bar=e.baz` would produce just two root nodes:
A `SqlAstJoin` node and a `SqlAstWhere` node, so you can control very easily what types of SQL-snippets are allowed and
which should be rejected.

Additionally to that , the parser is build in a modular way so that you can limit the SQL that it understands.
You know that at a certain point you expect only `ORDER BY` snippets with a very simple condition consisting of only
simple operations and a few functions like `COUNT` or `SUM`, then only include these modules in the parser.
If someone tries to be sneaky and submits something like `ORDER BY EVAL("DROP TABLE orders")`, the parser will reject
the unexpected call to the "EVAL" function.

This also automatically excludes any type of SQL specialities that might be a loophole, because these specialities are
not included in this SQL library (unless you code that in yourself).

In short: This library gives YOU control over what SQL is allowed and what is not allowed.
You define a white-list, and anything not on that whitelist is rejected. (Similar to an HTML-filter in a forum)

## The client side

This library has two sides: A server side in PHP, and a client side written in Typescript.
Both parts contain the same dynamic SQL parser, but the client side also contains some UI code to provide the user with
an easy-to-use widget to modify the SQL parts. After all, not everyone understands SQL.

Both sides (client and server) are testet against the same set of fixtures to ensure that both parsers are compatible.

Warning: the client side is far from finished and needs much work.

