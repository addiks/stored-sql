SELECT a.id,b.id,c.id
FROM a
LEFT JOIN b ON(a.id = b.id)
INNER JOIN c USING(`id`)
