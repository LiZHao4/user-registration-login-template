import fs from 'fs'
interface IniConfig {
  [section: string]: { [key: string]: string }
}
export function parseIniFile(filePath: string): IniConfig {
  const content = fs.readFileSync(filePath, 'utf-8')
  const config: IniConfig = {}
  let currentSection: string | null = null
  content.split(/\r?\n/).forEach(line => {
    let cleanLine = line.split(';')[0].split('#')[0].trim()
    if (cleanLine === '') return
    const sectionMatch = cleanLine.match(/^\[(.*)\]$/)
    if (sectionMatch) {
      currentSection = sectionMatch[1].trim()
      if (!config[currentSection]) {
        config[currentSection] = {}
      }
      return
    }
    const keyValueMatch = cleanLine.match(/^(\w+)\s*=\s*(.*)$/)
    if (keyValueMatch && currentSection) {
      let key = keyValueMatch[1].trim()
      let value = keyValueMatch[2].trim()
      if ((value.startsWith('"') && value.endsWith('"')) ||
          (value.startsWith("'") && value.endsWith("'"))) {
        value = value.slice(1, -1)
      }
      config[currentSection][key] = value
    }
  })
  return config
}