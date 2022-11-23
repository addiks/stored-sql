SELECT COUNT(*) 
FROM foo t0 
WHERE (
    (
        (
            t0.created > ? AND t0.bar = ?
        ) 
        AND t0.baz = ?
    ) 
    AND t0.reason = ?
)
