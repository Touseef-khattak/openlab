import ReactMarkdown from 'react-markdown';

/**
 * Renders a Markdown string with Tailwind-styled elements. Element styling is
 * set explicitly here because Tailwind's preflight strips list markers and
 * heading sizes, so bare react-markdown output would look unstyled.
 * react-markdown does not render raw HTML by default, so LLM output is safe.
 */
export function Markdown({ content, className = '' }: { content: string; className?: string }) {
    return (
        <div className={`text-sm leading-relaxed ${className}`}>
            <ReactMarkdown
                components={{
                    p: ({ children }) => <p className="mb-2 last:mb-0">{children}</p>,
                    ul: ({ children }) => <ul className="mb-2 list-disc space-y-0.5 pl-5">{children}</ul>,
                    ol: ({ children }) => <ol className="mb-2 list-decimal space-y-0.5 pl-5">{children}</ol>,
                    li: ({ children }) => <li className="pl-0.5">{children}</li>,
                    strong: ({ children }) => <strong className="font-semibold">{children}</strong>,
                    em: ({ children }) => <em className="italic">{children}</em>,
                    h1: ({ children }) => <h3 className="mt-3 mb-1 font-semibold first:mt-0">{children}</h3>,
                    h2: ({ children }) => <h3 className="mt-3 mb-1 font-semibold first:mt-0">{children}</h3>,
                    h3: ({ children }) => <h4 className="mt-2 mb-1 font-semibold first:mt-0">{children}</h4>,
                    a: ({ href, children }) => (
                        <a href={href} target="_blank" rel="noopener noreferrer" className="underline underline-offset-2">
                            {children}
                        </a>
                    ),
                    code: ({ children }) => <code className="bg-muted rounded px-1 py-0.5 text-xs">{children}</code>,
                    hr: () => <hr className="my-3" />,
                }}
            >
                {content}
            </ReactMarkdown>
        </div>
    );
}
