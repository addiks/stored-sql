
import { SqlParser, defaultParser, SqlAstNode, UnparsableSqlException } from 'storedsql';
import { readFileSync, readdirSync, realpathSync, existsSync } from 'fs';

function testSqlFile(sqlFileName, sqlFilePath, astFilePath)
{
    test("Should parse SQL in " + sqlFileName, () => {
        var sql = readFileSync(sqlFilePath, 'utf8');
        var expectedAstDump = readFileSync(astFilePath, 'utf8').trim();
    
        let parser: SqlParser = defaultParser();
        
        try {
            let detectedContent: Array<SqlAstNode> = parser.parseSql(sql);
            
            //console.log([detectedContent, sql, parser]);
            
            let actualAstDump: string = dumpNodes(detectedContent);
            
            expect(actualAstDump).toBe(expectedAstDump);
            
        } catch (exception) {
            if (exception instanceof UnparsableSqlException) {
                console.log(exception.asciiLocationDump());
                console.log(exception);
                
            } else {
                throw exception;
            }
        }
    });
}

function dumpNodes(
    nodes: Array<SqlAstNode>, 
    level: number = 0, 
    withSql: boolean = false
): string {
    let dumpLines: Array<string> = [];
    
    for (var index in nodes) {
        let node: SqlAstNode = nodes[index];
        
        let line: string = ''.padStart(level, '-') + node.nodeType;
        
        if (withSql) {
            line += ':' + node.toSql();
        }
        
        dumpLines.push(line);
        
        let children: Array<SqlAstNode> = node.children();
        
        if (children.length > 0) {
            dumpLines.push(dumpNodes(children, level + 1, withSql))
        }
    }
    
    return dumpLines.join("\n");
}

var fixtureFolder = realpathSync(__dirname + '/../../../fixtures');

var sqlFiles = readdirSync(fixtureFolder).filter(filePath => filePath.endsWith('.sql'));

for (var index in sqlFiles) {
    var sqlFileName = sqlFiles[index];
    var sqlFilePath = fixtureFolder + "/" + sqlFileName;
    var astFilePath = sqlFilePath + ".ast";
    
    if (existsSync(astFilePath)) {
        testSqlFile(
            sqlFileName, 
            sqlFilePath, 
            astFilePath, 
        );
    }
}

