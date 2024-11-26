SELECT a.id, (SELECT b.foo FROM b WHERE b.a_id = a.id) as b_foo
FROM a
