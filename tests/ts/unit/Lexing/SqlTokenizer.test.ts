
import { UnlexableSqlException, SqlTokenizer, defaultTokenizer } from 'storedsql';
import { sprintf } from 'sprintf-js';
import { readFileSync, readdirSync, realpathSync, existsSync } from 'fs';

let tokenizer: SqlTokenizer = defaultTokenizer();

function testSqlFile(sqlFileName, sqlFilePath, tokensFilePath)
{
    test("Should detect correct tokens in " + sqlFileName, () => {
        var sql = readFileSync(sqlFilePath, 'utf8');
        var expectedTokenString = readFileSync(tokensFilePath, 'utf8').trim();
        
        try {
            var actualTokens = tokenizer.tokenize(sql);
            actualTokens = actualTokens.withoutWhitespace();
            
        } catch (exception) {
            if (exception instanceof UnlexableSqlException) {
                console.log(exception.asciiLocationDump());
            }
            
            throw exception;
        }
        
        var actualTokenLines = [];
        for (var index in actualTokens.tokens) {
            var token = actualTokens.tokens[index];
            
            actualTokenLines.push(sprintf(
                '%d,%d,%s',
                token.line(),
                token.offset(),
                token.token().name()
            ));
        }
        
        var actualTokenString = actualTokenLines.join("\n");
        
        expect(actualTokenString).toBe(expectedTokenString);
    });
}

var fixtureFolder = realpathSync(__dirname + '/../../../fixtures');

var sqlFiles = readdirSync(fixtureFolder).filter(filePath => filePath.endsWith('.sql'));

for (var index in sqlFiles) {
    var sqlFileName = sqlFiles[index];
    var sqlFilePath = fixtureFolder + "/" + sqlFileName;
    var tokensFilePath = sqlFilePath + ".tokens";
    
    if (existsSync(tokensFilePath)) {
        testSqlFile(
            sqlFileName, 
            sqlFilePath, 
            tokensFilePath, 
        );
    }
}

