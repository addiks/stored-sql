import { defaultParser, UnparsableSqlException } from 'storedsql';
import { readFileSync, readdirSync, realpathSync, existsSync } from 'fs';
function testSqlFile(sqlFileName, sqlFilePath, astFilePath) {
    test("Should parse SQL in " + sqlFileName, () => {
        var sql = readFileSync(sqlFilePath, 'utf8');
        var expectedAstDump = readFileSync(astFilePath, 'utf8').trim();
        let parser = defaultParser();
        try {
            let detectedContent = parser.parseSql(sql);
            //console.log([detectedContent, sql, parser]);
            let actualAstDump = dumpNodes(detectedContent);
            expect(actualAstDump).toBe(expectedAstDump);
        }
        catch (exception) {
            if (exception instanceof UnparsableSqlException) {
                console.log(exception.asciiLocationDump());
                console.log(exception);
            }
            else {
                throw exception;
            }
        }
    });
}
function dumpNodes(nodes, level = 0, withSql = false) {
    let dumpLines = [];
    for (var index in nodes) {
        let node = nodes[index];
        let line = ''.padStart(level, '-') + node.nodeType;
        if (withSql) {
            line += ':' + node.toSql();
        }
        dumpLines.push(line);
        let children = node.children();
        if (children.length > 0) {
            dumpLines.push(dumpNodes(children, level + 1, withSql));
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
        testSqlFile(sqlFileName, sqlFilePath, astFilePath);
    }
}
