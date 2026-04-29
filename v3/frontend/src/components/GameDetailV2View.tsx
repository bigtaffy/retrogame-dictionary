import { Fragment } from 'react'
import { useNavigate } from 'react-router-dom'
import type { ConsoleSlug } from '../config/consoles'
import type { V2Game } from '../types/v2Game'
import { detectLang, fmtCategory, pickTitle, ratingBadge } from '../lib/v2GameUi'
import { useLightbox } from '../context/LightboxContext'

type Shot = string | { url?: string }

function shotSrc(s: Shot): string {
  if (typeof s === 'string') {
    return s
  }
  return s?.url || ''
}

export function GameDetailV2View({ g, consoleName }: { g: V2Game; consoleName: ConsoleSlug }) {
  const nav = useNavigate()
  const lb = useLightbox()
  const primary = pickTitle(g)
  const primaryLang = detectLang(g)
  const fmt = fmtCategory(g.format_category, g.format)
  const rb = ratingBadge(g.rating)
  const region = g.region_category || ''
  const year = g.release_date
    ? (g.release_date.match(/\d{4}/) || [''])[0]
    : (g.year || '')

  const titles: { lang: string; text: string }[] = []
  if (g.title_en) {
    titles.push({ lang: 'en', text: g.title_en })
  }
  if (g.title_jp) {
    titles.push({ lang: 'jp', text: g.title_jp })
  }
  if (g.title_zh) {
    titles.push({ lang: 'zh', text: g.title_zh })
  }

  const specsRows: [string, string][] = []
  if (g.maker || g.publisher) {
    specsRows.push(['Publisher', (g.maker || g.publisher) as string])
  }
  if (g.developer) {
    specsRows.push(['Developer', g.developer])
  }
  if (g.release_date) {
    specsRows.push(['Released', g.release_date])
  } else if (year) {
    specsRows.push(['Year', year])
  }
  if (g.genre_category) {
    specsRows.push(['Genre', g.genre_category])
  }
  if (fmt) {
    specsRows.push(['Format', fmt.label])
  }
  if (region) {
    specsRows.push(['Region', region])
  }
  if (g.fc_no) {
    specsRows.push(['FC No.', g.fc_no])
  }
  if (g.price) {
    specsRows.push(['Price', g.price])
  }
  if (g.media) {
    specsRows.push(['Media', g.media])
  }
  if (g.product_code) {
    specsRows.push(['Code', g.product_code])
  }

  const shots: Shot[] = g.screenshots || []
  const yts: unknown[] = g.youtube || []

  return (
    <div className="detail">
      <button type="button" className="back" onClick={() => nav(`/${consoleName}`)}>
        ← BACK
      </button>
      <div className="header">
        <div
          className="cover-large"
          style={
            g.cover
              ? { backgroundImage: `url('${g.cover.replace(/'/g, "\\'")}')` }
              : undefined
          }
        />
        <div className="header-info">
          <h2 className={primaryLang === 'en' ? '' : 'cjk'}>{primary}</h2>
          {titles
            .filter((t) => t.lang !== primaryLang)
            .map((t) => (
              <div
                key={t.lang + t.text}
                className={t.lang === 'jp' ? 'subtitle-jp' : 'subtitle-zh'}
              >
                <span className={'lang-chip ' + t.lang}>{t.lang.toUpperCase()}</span>
                {t.text}
              </div>
            ))}
          <div className="header-badges">
            {fmt ? <span className={'badge ' + fmt.cls}>{fmt.label}</span> : null}
            {rb ? <span className={'badge ' + rb.cls}>{rb.label}</span> : null}
            {region ? <span className={'badge region ' + region}>{region}</span> : null}
          </div>
          <div className="header-actions">
            {g.source_url ? (
              <a
                className="action-btn src"
                href={g.source_url}
                target="_blank"
                rel="noopener noreferrer"
              >
                ↗ SOURCE
              </a>
            ) : null}
          </div>
        </div>
      </div>

      {specsRows.length > 0 ? (
        <div className="section">
          <div className="section-title">★ Game Specs</div>
          <dl className="specs">
            {specsRows.map(([k, v]) => (
              <Fragment key={k + v}>
                <dt>{k}</dt>
                <dd>{v}</dd>
              </Fragment>
            ))}
          </dl>
        </div>
      ) : null}

      {g.overview_zh || g.overview_en || g.wiki_extract_en ? (
        <div className="section">
          <div className="section-title">▣ Description</div>
          {g.overview_zh ? (
            <div className="desc">
              <span className="desc-label">中</span>
              {g.overview_zh}
            </div>
          ) : null}
          {g.overview_en ? (
            <details className="desc-collapsible">
              <summary className="desc-summary">
                <span className="desc-label">EN</span>顯示英文原文
              </summary>
              <div className="desc desc-en-body">{g.overview_en}</div>
            </details>
          ) : null}
        </div>
      ) : null}

      {g.comment_zh || g.comment_en ? (
        <div className="section">
          <div className="section-title">✦ Review</div>
          {g.comment_zh ? (
            <div className="desc">
              <span className="desc-label">中</span>
              {g.comment_zh}
            </div>
          ) : null}
          {g.comment_en ? (
            <details className="desc-collapsible">
              <summary className="desc-summary">
                <span className="desc-label">EN</span>顯示英文原文
              </summary>
              <div className="desc desc-en-body">{g.comment_en}</div>
            </details>
          ) : null}
        </div>
      ) : null}

      {shots.length > 0 ? (
        <div className="section">
          <div className="section-title">▦ Screenshots</div>
          <div className="gallery">
            {shots.slice(0, 12).map((s) => {
              const u = shotSrc(s)
              if (!u) {
                return null
              }
              return (
                <img
                  key={u}
                  src={u}
                  alt=""
                  decoding="async"
                  data-shot={u}
                  onClick={() => lb.open(<img src={u} alt="" />)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      lb.open(<img src={u} alt="" />)
                    }
                  }}
                />
              )
            })}
          </div>
        </div>
      ) : null}

      {yts.length > 0 ? (
        <div className="section">
          <div className="section-title">▶ Videos</div>
          <div className="videos">
            {yts.slice(0, 6).map((v) => {
              let vid = ''
              let vtitle = ''
              if (typeof v === 'string') {
                vid = /^[\w-]{11}$/.test(v)
                  ? v
                  : (v.match(/(?:v=|\/)([\w-]{11})/)?.[1] as string) || ''
              } else if (v && typeof v === 'object') {
                const o = v as { id?: string; videoId?: string; url?: string; title?: string }
                vid =
                  o.id ||
                  o.videoId ||
                  (o.url && (o.url.match(/(?:v=|\/)([\w-]{11})/)?.[1] as string)) ||
                  ''
                vtitle = o.title || ''
              }
              const thumb = vid ? `https://img.youtube.com/vi/${vid}/hqdefault.jpg` : ''
              return (
                <div
                  key={String(vid) + vtitle}
                  className="video-thumb"
                  style={thumb ? { backgroundImage: `url('${thumb}')` } : undefined}
                  onClick={() => {
                    if (!vid) {
                      return
                    }
                    lb.open(
                      <iframe
                        title="YouTube"
                        src={`https://www.youtube.com/embed/${vid}?autoplay=1`}
                        allow="autoplay; encrypted-media"
                        allowFullScreen
                      />,
                    )
                  }}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      if (vid) {
                        lb.open(
                          <iframe
                            title="YouTube"
                            src={`https://www.youtube.com/embed/${vid}?autoplay=1`}
                            allow="autoplay; encrypted-media"
                            allowFullScreen
                          />,
                        )
                      }
                    }
                  }}
                >
                  {vtitle ? <div className="v-title">{vtitle}</div> : null}
                </div>
              )
            })}
          </div>
        </div>
      ) : null}
    </div>
  )
}
