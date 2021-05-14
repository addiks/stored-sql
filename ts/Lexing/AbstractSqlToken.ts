/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

/**
 * If you want to extend the tokenizing capabilities and / or build your own tokenizer that knows about other
 * tokens then the ones this package ships with, then create a new sql-token enumeration class that inherits from this
 * abstract enum class.
 *
 * If you just want to add a few tokens without any special new mechanics,
 * then you can simply use a custom keywords-mapping with the default tokenizer.
 *
 * @see SqlTokenizerClass.defaultKeywords()
 */
export abstract class AbstractSqlToken
{
}
