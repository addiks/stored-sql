SELECT DISTINCT 
    s0_.id AS id_0, 
    s0_.created AS created_1 
FROM sales_order s0_ 
LEFT JOIN sales_channel s1_ ON s0_.origin_channel = s1_.id 
LEFT JOIN sales_channel s2_ ON s0_.origin_channel = s2_.id 
LEFT JOIN sales_order_stati s3_ ON s0_.status = s3_.name 
LEFT JOIN core_tag_set c4_ ON s0_.tagSet_id = c4_.id 
GROUP BY s0_.id 
ORDER BY s0_.created DESC 
LIMIT 10
