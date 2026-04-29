import { Link } from 'react-router-dom'
import type { ConsoleSlug } from '../config/consoles'
import type { V2Game } from '../types/v2Game'
import { detectLang, fmtCategory, langClass, pickTitle, ratingBadge } from '../lib/v2GameUi'
import { resolveV2Image } from '../api/v2StaticGames'

type ViewMode = 'grid' | 'list' | 'covers'

function shotUrl(
  s: string | { url?: string } | null | undefined,
  consoleName: ConsoleSlug,
): string {
  if (typeof s === 'string') {
    return resolveV2Image(s, consoleName)
  }
  if (s && typeof s === 'object' && s.url) {
    return resolveV2Image(s.url, consoleName)
  }
  return ''
}

export function GameCard({ g, consoleName, view }: { g: V2Game; consoleName: ConsoleSlug; view: ViewMode }) {
  const title = pickTitle(g)
  const lang = detectLang(g)
  const tCls = langClass(lang)
  const altTitle =
    (lang !== 'zh' && g.title_zh
      ? g.title_zh
      : lang !== 'en' && g.title_en
        ? g.title_en
        : lang !== 'jp' && g.title_jp
          ? g.title_jp
          : '') || ''
  const fmt = fmtCategory(g.format_category, g.format)
  const rb = ratingBadge(g.rating)
  const region = g.region_category || (g.regions && g.regions[0]) || ''
  const year = g.release_date
    ? (g.release_date.match(/\d{4}/) || [''])[0]
    : (g.year || '')

  const miniShots = (g.screenshots || [])
    .slice(0, 2)
    .map((s) => shotUrl(s, consoleName))
    .filter(Boolean)

  return (
    <Link
      to={`/${consoleName}/g/${encodeURIComponent(g.id)}`}
      className="card"
      data-title={title}
      data-id={g.id}
    >
      <div className={'cover' + (g.cover ? '' : ' no-img')}>
        {g.cover ? <img className="cover-img" src={g.cover} alt="" decoding="async" /> : null}
      </div>
      <div className="info">
        <div className={`title ${tCls}`}>{title}</div>
        {altTitle ? <div className="subtitle">{altTitle}</div> : null}
        <div className="meta">
          {g.maker || g.publisher ? <span>{g.maker || g.publisher}</span> : null}
          {(g.maker || g.publisher) && year ? <span className="dot">·</span> : null}
          {year ? <span>{year}</span> : null}
        </div>
        <div className="badges">
          {fmt ? <span className={'badge ' + fmt.cls}>{fmt.label}</span> : null}
          {rb ? <span className={'badge ' + rb.cls}>{rb.label}</span> : null}
          {region ? <span className={'badge region ' + region}>{region}</span> : null}
        </div>
        {view === 'list' && miniShots.length > 0 ? (
          <div className="list-shots">
            {miniShots.map((u) => (
              <img key={u} src={u} alt="" decoding="async" />
            ))}
          </div>
        ) : null}
      </div>
    </Link>
  )
}
